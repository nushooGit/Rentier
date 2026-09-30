import { Form } from '@inertiajs/react';
import { useMemo, useState, type ReactNode } from 'react';
import { useI18n } from '@/lib/i18n';
import DateInput from '@/components/date-input';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    expenseCategoryLabel,
    expensePaidByLabel,
    expenseResponsiblePartyLabel,
    expenseSettlementTypeLabel,
} from '@/pages/expenses/labels';
import type {
    Expense,
    ExpenseCategory,
    ExpenseLeaseOption,
    ExpenseOption,
    ExpensePaidBy,
    ExpensePropertyOption,
    ExpenseResponsibleParty,
    ExpenseSettlementType,
} from '@/types';

type Props = {
    action: {
        action: string;
        method: 'get' | 'post' | 'put' | 'patch' | 'delete';
    };
    submitLabel: string;
    expense?: Expense;
    properties: ExpensePropertyOption[];
    leases: ExpenseLeaseOption[];
    expenseCategories: ExpenseOption<ExpenseCategory>[];
    expensePaidByOptions: ExpenseOption<ExpensePaidBy>[];
    expenseResponsiblePartyOptions: ExpenseOption<ExpenseResponsibleParty>[];
    expenseSettlementTypeOptions: ExpenseOption<ExpenseSettlementType>[];
};

function fieldValue(value: string | number | null | undefined) {
    return value ?? '';
}

function Field({
    children,
    className = '',
}: {
    children: ReactNode;
    className?: string;
}) {
    return <div className={`grid gap-1.5 ${className}`}>{children}</div>;
}

function FormSection({
    title,
    children,
    gridClassName = 'md:grid-cols-2 xl:grid-cols-4',
}: {
    title: string;
    children: ReactNode;
    gridClassName?: string;
}) {
    return (
        <section className="space-y-3 rounded-2xl border border-border/70 bg-card/80 p-4 shadow-sm sm:p-5">
            <h2 className="text-sm font-semibold tracking-tight sm:text-base">{title}</h2>
            <div className={`grid gap-3 ${gridClassName}`}>{children}</div>
        </section>
    );
}

const inputClassName = 'h-10';

const selectClassName =
    'border-input bg-background ring-offset-background placeholder:text-muted-foreground focus-visible:ring-ring flex h-10 w-full rounded-xl border px-3 py-1 text-sm shadow-xs transition-colors focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50 md:h-8';

const textareaClassName =
    'border-input bg-background ring-offset-background placeholder:text-muted-foreground focus-visible:ring-ring flex min-h-28 w-full rounded-xl border px-3 py-2.5 text-sm shadow-xs transition-colors focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50 md:min-h-20';

function allowedSettlementTypes(
    paidBy: ExpensePaidBy,
    responsibleParty: ExpenseResponsibleParty,
): ExpenseSettlementType[] {
    if (paidBy === 'tenant' && responsibleParty === 'owner') {
        return ['deduct_from_rent', 'deduct_from_utilities', 'reimburse'];
    }

    if (paidBy === 'owner' && responsibleParty === 'tenant') {
        return ['reimburse'];
    }

    return ['none'];
}

function dateIsInsideLease(date: string, lease: ExpenseLeaseOption): boolean {
    return (
        lease.start_date <= date &&
        (lease.end_date === null || lease.end_date >= date)
    );
}

export default function ExpenseForm({
    action,
    submitLabel,
    expense,
    properties,
    leases,
    expenseCategories,
    expensePaidByOptions,
    expenseResponsiblePartyOptions,
    expenseSettlementTypeOptions,
}: Props) {
    const { t } = useI18n();
    const now = new Date();
    const initialExpenseDate =
        expense?.expense_date ?? now.toISOString().slice(0, 10);
    const [selectedPropertyId, setSelectedPropertyId] = useState(
        fieldValue(expense?.property_id).toString(),
    );
    const [expenseDate, setExpenseDate] = useState(initialExpenseDate);
    const [paidBy, setPaidBy] = useState<ExpensePaidBy>(
        expense?.paid_by ?? 'owner',
    );
    const [responsibleParty, setResponsibleParty] =
        useState<ExpenseResponsibleParty>(
            expense?.responsible_party ?? 'owner',
        );
    const [settlementType, setSettlementType] = useState<ExpenseSettlementType>(
        expense?.settlement_type ?? 'none',
    );
    const [selectedLeaseId, setSelectedLeaseId] = useState(
        fieldValue(expense?.lease_id).toString(),
    );
    const propertyLeases = useMemo(
        () =>
            leases.filter(
                (lease) => lease.property_id.toString() === selectedPropertyId,
            ),
        [leases, selectedPropertyId],
    );
    const applicableLeases = useMemo(
        () =>
            selectedPropertyId !== '' && expenseDate !== ''
                ? propertyLeases.filter((lease) =>
                      dateIsInsideLease(expenseDate, lease),
                  )
                : [],
        [expenseDate, propertyLeases, selectedPropertyId],
    );
    const hasActiveTenantContext = useMemo(
        () => applicableLeases.length > 0,
        [applicableLeases],
    );
    const effectivePaidBy = hasActiveTenantContext ? paidBy : 'owner';
    const effectiveResponsibleParty = hasActiveTenantContext
        ? responsibleParty
        : 'owner';
    const visiblePaidByOptions = hasActiveTenantContext
        ? expensePaidByOptions
        : expensePaidByOptions.filter((option) => option.value === 'owner');
    const visibleResponsiblePartyOptions = hasActiveTenantContext
        ? expenseResponsiblePartyOptions
        : expenseResponsiblePartyOptions.filter(
              (option) => option.value === 'owner',
          );
    const allowedSettlements = allowedSettlementTypes(
        effectivePaidBy,
        effectiveResponsibleParty,
    );
    const effectiveSettlementType = allowedSettlements.includes(settlementType)
        ? settlementType
        : allowedSettlements[0];
    const tenantInvolved =
        effectivePaidBy === 'tenant' || effectiveResponsibleParty === 'tenant';
    const effectiveLeaseId =
        tenantInvolved && applicableLeases.length === 1
            ? applicableLeases[0].id.toString()
            : selectedLeaseId;
    const visibleSettlementTypeOptions = expenseSettlementTypeOptions.filter(
        (settlementTypeOption) =>
            allowedSettlements.includes(settlementTypeOption.value),
    );
    const settlementHelpText =
        effectivePaidBy === 'owner' && effectiveResponsibleParty === 'tenant'
            ? 'Dacă proprietarul plătește o cost suportat de chiriaș, aceasta se recuperează de la chiriaș.'
            : effectivePaidBy === 'tenant' &&
                effectiveResponsibleParty === 'owner'
              ? 'Dacă chiriașul plătește o cost suportat de proprietar, aceasta se scade din chirie, se scade din utilități sau se rambursează către chiriaș.'
              : null;

    return (
        <Form {...action} className="space-y-3.5">
            {({ errors, processing }) => (
                <>
                    <FormSection title={t('expenses.section.details')}>
                        <Field className="md:col-span-2">
                            <Label htmlFor="title">{t('expenses.field.title')}</Label>
                            <Input
                                id="title"
                                name="title"
                                className={inputClassName}
                                defaultValue={fieldValue(expense?.title)}
                                required
                                data-test="expense-title-input"
                            />
                            <InputError message={errors.title} />
                        </Field>

                        <Field>
                            <Label htmlFor="category">{t('expenses.field.category')}</Label>
                            <select
                                id="category"
                                name="category"
                                className={selectClassName}
                                defaultValue={expense?.category ?? 'other'}
                                required
                                data-test="expense-category-select"
                            >
                                {expenseCategories.map((category) => (
                                    <option
                                        key={category.value}
                                        value={category.value}
                                    >
                                        {expenseCategoryLabel(category.value)}
                                    </option>
                                ))}
                            </select>
                            <InputError message={errors.category} />
                        </Field>
                    </FormSection>

                    <FormSection title="Proprietate și contract">
                        <Field className="md:col-span-2">
                            <Label htmlFor="property_id">{t('common.property')}</Label>
                            <select
                                id="property_id"
                                name="property_id"
                                className={selectClassName}
                                value={selectedPropertyId}
                                onChange={(event) => {
                                    setSelectedPropertyId(event.target.value);
                                    setSelectedLeaseId('');
                                }}
                                required
                                data-test="expense-property-select"
                            >
                                <option value="" disabled>
                                    Alege proprietatea
                                </option>
                                {properties.map((property) => (
                                    <option
                                        key={property.id}
                                        value={property.id}
                                    >
                                        {property.name} · {property.city}
                                    </option>
                                ))}
                            </select>
                            <InputError message={errors.property_id} />
                        </Field>

                        <Field className="md:col-span-2">
                            <Label htmlFor="lease_id">{t('common.lease')}</Label>
                            <select
                                id="lease_id"
                                name="lease_id"
                                className={selectClassName}
                                value={effectiveLeaseId}
                                onChange={(event) =>
                                    setSelectedLeaseId(event.target.value)
                                }
                            >
                                <option value="">Fără contract</option>
                                {propertyLeases.map((lease) => (
                                    <option key={lease.id} value={lease.id}>
                                        {lease.label}
                                    </option>
                                ))}
                            </select>
                            <InputError message={errors.lease_id} />
                            {tenantInvolved && applicableLeases.length === 1 ? (
                                <p className="text-xs text-muted-foreground">
                                    Costul va fi asociat contractului activ
                                    la data selectată.
                                </p>
                            ) : null}
                        </Field>
                    </FormSection>

                    <FormSection title="Sumă și plată">
                        <Field>
                            <Label htmlFor="amount">{t('expenses.field.amount')}</Label>
                            <Input
                                id="amount"
                                name="amount"
                                className={inputClassName}
                                type="number"
                                min="0"
                                step="0.01"
                                defaultValue={fieldValue(expense?.amount)}
                                required
                                data-test="expense-amount-input"
                            />
                            <InputError message={errors.amount} />
                        </Field>

                        <Field>
                            <Label htmlFor="currency">{t('common.currency')}</Label>
                            <Input
                                id="currency"
                                name="currency"
                                className={inputClassName}
                                maxLength={3}
                                defaultValue={fieldValue(
                                    expense?.currency ?? 'RON',
                                )}
                                required
                            />
                            <InputError message={errors.currency} />
                        </Field>

                        <Field>
                            <Label htmlFor="expense_date">
                                Data costului
                            </Label>
                            <DateInput
                                id="expense_date"
                                name="expense_date"
                                className={inputClassName}
                                defaultValue={initialExpenseDate}
                                onValueChange={(value) => {
                                    setExpenseDate(value);

                                    if (tenantInvolved) {
                                        setSelectedLeaseId('');
                                    }
                                }}
                                required
                            />
                            <InputError message={errors.expense_date} />
                        </Field>

                        <Field>
                            <Label htmlFor="paid_by">{t('expenses.field.paidBy')}</Label>
                            <select
                                id="paid_by"
                                name="paid_by"
                                className={selectClassName}
                                value={effectivePaidBy}
                                onChange={(event) =>
                                    setPaidBy(
                                        event.target.value as ExpensePaidBy,
                                    )
                                }
                                required
                            >
                                {visiblePaidByOptions.map((paidBy) => (
                                    <option
                                        key={paidBy.value}
                                        value={paidBy.value}
                                    >
                                        {expensePaidByLabel(paidBy.value)}
                                    </option>
                                ))}
                            </select>
                            <InputError message={errors.paid_by} />
                        </Field>

                        <Field>
                            <Label htmlFor="responsible_party">
                                Cine suportă costul?
                            </Label>
                            <select
                                id="responsible_party"
                                name="responsible_party"
                                className={selectClassName}
                                value={effectiveResponsibleParty}
                                onChange={(event) =>
                                    setResponsibleParty(
                                        event.target
                                            .value as ExpenseResponsibleParty,
                                    )
                                }
                                required
                            >
                                {visibleResponsiblePartyOptions.map(
                                    (responsibleParty) => (
                                        <option
                                            key={responsibleParty.value}
                                            value={responsibleParty.value}
                                        >
                                            {expenseResponsiblePartyLabel(
                                                responsibleParty.value,
                                            )}
                                        </option>
                                    ),
                                )}
                            </select>
                            <InputError message={errors.responsible_party} />
                        </Field>

                        <Field>
                            <Label htmlFor="settlement_type">Decontare</Label>
                            <select
                                id="settlement_type"
                                name="settlement_type"
                                className={selectClassName}
                                value={effectiveSettlementType}
                                onChange={(event) =>
                                    setSettlementType(
                                        event.target
                                            .value as ExpenseSettlementType,
                                    )
                                }
                                required
                            >
                                {visibleSettlementTypeOptions.map(
                                    (settlementType) => (
                                        <option
                                            key={settlementType.value}
                                            value={settlementType.value}
                                        >
                                            {expenseSettlementTypeLabel(
                                                settlementType.value,
                                                effectivePaidBy,
                                                effectiveResponsibleParty,
                                            )}
                                        </option>
                                    ),
                                )}
                            </select>
                            <InputError message={errors.settlement_type} />
                        </Field>

                        <div className="space-y-1 text-xs text-muted-foreground md:col-span-2 xl:col-span-4">
                            {!hasActiveTenantContext ? (
                                <p>
                                    Nu există contract activ pentru această
                                    proprietate la data selectată. Poți
                                    înregistra doar costuri plătite și
                                    suportate de proprietar.
                                </p>
                            ) : null}
                            <p>
                                Costurile suportate de chiriaș nu afectează
                                profitul proprietarului.
                            </p>
                            {settlementHelpText ? (
                                <p>{settlementHelpText}</p>
                            ) : null}
                        </div>
                    </FormSection>

                    <section className="space-y-3 rounded-2xl border border-border/70 bg-card/80 p-4 shadow-sm sm:p-5">
                        <h2 className="text-sm font-semibold tracking-tight sm:text-base">
                            Note
                        </h2>
                        <Field>
                            <Label htmlFor="notes">{t('common.notes')}</Label>
                            <textarea
                                id="notes"
                                name="notes"
                                className={textareaClassName}
                                rows={5}
                                defaultValue={fieldValue(expense?.notes)}
                            />
                            <InputError message={errors.notes} />
                        </Field>
                    </section>

                    <div className="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                        <Button
                            type="submit"
                            disabled={processing || properties.length === 0}
                            data-test="expense-save-button"
                        >
                            {submitLabel}
                        </Button>
                    </div>
                </>
            )}
        </Form>
    );
}
