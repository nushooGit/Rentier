import { Form } from '@inertiajs/react';
import type { ReactNode } from 'react';
import DateInput from '@/components/date-input';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    expenseCategoryLabel,
    expensePaidByLabel,
    expenseStatusLabel,
} from '@/pages/expenses/labels';
import type {
    Expense,
    ExpenseCategory,
    ExpenseLeaseOption,
    ExpenseOption,
    ExpensePaidBy,
    ExpensePropertyOption,
    ExpenseStatus,
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
    expenseStatuses: ExpenseOption<ExpenseStatus>[];
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
        <section className="space-y-2.5 rounded-lg border p-3 sm:p-3.5">
            <h2 className="text-sm font-medium sm:text-base">{title}</h2>
            <div className={`grid gap-3 ${gridClassName}`}>{children}</div>
        </section>
    );
}

const inputClassName = 'md:h-8';

const selectClassName =
    'border-input bg-background ring-offset-background placeholder:text-muted-foreground focus-visible:ring-ring flex h-9 w-full rounded-md border px-3 py-1 text-sm shadow-xs transition-colors focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50 md:h-8';

const textareaClassName =
    'border-input bg-background ring-offset-background placeholder:text-muted-foreground focus-visible:ring-ring flex min-h-24 w-full rounded-md border px-3 py-2 text-sm shadow-xs transition-colors focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50 md:min-h-20';

export default function ExpenseForm({
    action,
    submitLabel,
    expense,
    properties,
    leases,
    expenseCategories,
    expensePaidByOptions,
    expenseStatuses,
}: Props) {
    const now = new Date();

    return (
        <Form {...action} className="space-y-3.5">
            {({ errors, processing }) => (
                <>
                    <FormSection title="Detalii cheltuială">
                        <Field className="md:col-span-2">
                            <Label htmlFor="title">Titlu</Label>
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
                            <Label htmlFor="category">Categorie</Label>
                            <select
                                id="category"
                                name="category"
                                className={selectClassName}
                                defaultValue={
                                    expense?.category ?? 'maintenance'
                                }
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

                        <Field>
                            <Label htmlFor="status">Status</Label>
                            <select
                                id="status"
                                name="status"
                                className={selectClassName}
                                defaultValue={expense?.status ?? 'paid'}
                                required
                                data-test="expense-status-select"
                            >
                                {expenseStatuses.map((status) => (
                                    <option
                                        key={status.value}
                                        value={status.value}
                                    >
                                        {expenseStatusLabel(status.value)}
                                    </option>
                                ))}
                            </select>
                            <InputError message={errors.status} />
                        </Field>
                    </FormSection>

                    <FormSection title="Proprietate și contract">
                        <Field className="md:col-span-2">
                            <Label htmlFor="property_id">Proprietate</Label>
                            <select
                                id="property_id"
                                name="property_id"
                                className={selectClassName}
                                defaultValue={fieldValue(expense?.property_id)}
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
                            <Label htmlFor="lease_id">Contract</Label>
                            <select
                                id="lease_id"
                                name="lease_id"
                                className={selectClassName}
                                defaultValue={fieldValue(expense?.lease_id)}
                            >
                                <option value="">Fără contract</option>
                                {leases.map((lease) => (
                                    <option key={lease.id} value={lease.id}>
                                        {lease.label}
                                    </option>
                                ))}
                            </select>
                            <InputError message={errors.lease_id} />
                        </Field>
                    </FormSection>

                    <FormSection title="Sumă și plată">
                        <Field>
                            <Label htmlFor="amount">Sumă</Label>
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
                            <Label htmlFor="currency">Monedă</Label>
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
                                Data cheltuielii
                            </Label>
                            <DateInput
                                id="expense_date"
                                name="expense_date"
                                className={inputClassName}
                                defaultValue={
                                    expense?.expense_date ??
                                    now.toISOString().slice(0, 10)
                                }
                                required
                            />
                            <InputError message={errors.expense_date} />
                        </Field>

                        <Field>
                            <Label htmlFor="paid_by">Plătitor</Label>
                            <select
                                id="paid_by"
                                name="paid_by"
                                className={selectClassName}
                                defaultValue={expense?.paid_by ?? 'landlord'}
                                required
                            >
                                {expensePaidByOptions.map((paidBy) => (
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
                    </FormSection>

                    <section className="space-y-2.5 rounded-lg border p-3 sm:p-3.5">
                        <h2 className="text-sm font-medium sm:text-base">
                            Note
                        </h2>
                        <Field>
                            <Label htmlFor="notes">Note</Label>
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
