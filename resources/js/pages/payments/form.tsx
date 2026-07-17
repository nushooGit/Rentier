import { Form } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { useState } from 'react';
import DateInput from '@/components/date-input';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { formatMoney } from '@/lib/money';
import { paymentMethodLabel, paymentTypeLabel } from '@/pages/payments/labels';
import type {
    PaymentLeaseOption,
    PaymentMethod,
    PaymentOption,
    PaymentType,
    RentPayment,
} from '@/types';

type Props = {
    action: {
        action: string;
        method: 'get' | 'post' | 'put' | 'patch' | 'delete';
    };
    submitLabel: string;
    payment?: RentPayment;
    leases: PaymentLeaseOption[];
    paymentMethods: PaymentOption<PaymentMethod>[];
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

export default function PaymentForm({
    action,
    submitLabel,
    payment,
    leases,
    paymentMethods,
}: Props) {
    const now = new Date();
    const [paymentType, setPaymentType] = useState<PaymentType>(
        payment?.payment_type ?? 'rent',
    );
    const [selectedLeaseId, setSelectedLeaseId] = useState(
        String(fieldValue(payment?.lease_id)),
    );
    const paymentTypeOptions: PaymentOption<PaymentType>[] = [
        { value: 'rent', label: 'Chirie' },
        { value: 'guarantee', label: 'Garanție' },
    ];
    const selectedLease = leases.find(
        (lease) => String(lease.id) === selectedLeaseId,
    );
    const currentPaymentAmount =
        paymentType === 'guarantee' && payment?.payment_type === 'guarantee'
            ? Number(payment.amount)
            : 0;
    const collectedGuaranteeExcludingCurrent = Math.max(
        Number(selectedLease?.guarantee_collected_amount ?? 0) -
            currentPaymentAmount,
        0,
    );
    const expectedGuarantee = Number(selectedLease?.deposit_amount ?? 0);
    const remainingGuarantee = Math.max(
        expectedGuarantee - collectedGuaranteeExcludingCurrent,
        0,
    );
    const guaranteeIsBlocked =
        paymentType === 'guarantee' &&
        selectedLease !== undefined &&
        remainingGuarantee <= 0;

    return (
        <Form {...action} className="space-y-3.5">
            {({ errors, processing }) => (
                <>
                    <FormSection title="Detalii plată">
                        <Field className="md:col-span-2">
                            <Label htmlFor="lease_id">Contract</Label>
                            <select
                                id="lease_id"
                                name="lease_id"
                                className={selectClassName}
                                value={selectedLeaseId}
                                onChange={(event) =>
                                    setSelectedLeaseId(event.target.value)
                                }
                                required
                                data-test="payment-lease-select"
                            >
                                <option value="" disabled>
                                    Alege contractul
                                </option>
                                {leases.map((lease) => (
                                    <option key={lease.id} value={lease.id}>
                                        {lease.label}
                                    </option>
                                ))}
                            </select>
                            <InputError message={errors.lease_id} />
                        </Field>

                        <Field>
                            <Label htmlFor="payment_type">Tip plată</Label>
                            <select
                                id="payment_type"
                                name="payment_type"
                                className={selectClassName}
                                value={paymentType}
                                onChange={(event) =>
                                    setPaymentType(
                                        event.target.value as PaymentType,
                                    )
                                }
                                required
                                data-test="payment-type-select"
                            >
                                {paymentTypeOptions.map((type) => (
                                    <option key={type.value} value={type.value}>
                                        {paymentTypeLabel(type.value)}
                                    </option>
                                ))}
                            </select>
                            <InputError message={errors.payment_type} />
                        </Field>

                        <Field>
                            <Label htmlFor="method">Metodă</Label>
                            <select
                                id="method"
                                name="method"
                                className={selectClassName}
                                defaultValue={fieldValue(payment?.method)}
                            >
                                <option value="">Nesetat</option>
                                {paymentMethods.map((method) => (
                                    <option
                                        key={method.value}
                                        value={method.value}
                                    >
                                        {paymentMethodLabel(method.value)}
                                    </option>
                                ))}
                            </select>
                            <InputError message={errors.method} />
                        </Field>
                    </FormSection>

                    <FormSection
                        title={
                            paymentType === 'rent'
                                ? 'Sumă și perioada chiriei'
                                : 'Sumă și garanție'
                        }
                    >
                        <Field>
                            <Label htmlFor="amount">Sumă</Label>
                            <Input
                                id="amount"
                                name="amount"
                                className={inputClassName}
                                type="number"
                                min="0"
                                max={
                                    paymentType === 'guarantee'
                                        ? remainingGuarantee
                                        : undefined
                                }
                                step="0.01"
                                defaultValue={fieldValue(payment?.amount)}
                                required
                                data-test="payment-amount-input"
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
                                    payment?.currency ?? 'RON',
                                )}
                                required
                            />
                            <InputError message={errors.currency} />
                        </Field>

                        <Field>
                            <Label htmlFor="payment_date">Data încasării</Label>
                            <DateInput
                                id="payment_date"
                                name="payment_date"
                                className={inputClassName}
                                defaultValue={
                                    payment?.payment_date ??
                                    now.toISOString().slice(0, 10)
                                }
                                required
                            />
                            <InputError message={errors.payment_date} />
                        </Field>

                        {paymentType === 'rent' ? (
                            <>
                                <Field>
                                    <Label htmlFor="period_month">
                                        Luna chiriei
                                    </Label>
                                    <Input
                                        id="period_month"
                                        name="period_month"
                                        className={inputClassName}
                                        type="number"
                                        min="1"
                                        max="12"
                                        step="1"
                                        defaultValue={fieldValue(
                                            payment?.period_month ??
                                                now.getMonth() + 1,
                                        )}
                                        required
                                    />
                                    <InputError message={errors.period_month} />
                                </Field>

                                <Field>
                                    <Label htmlFor="period_year">
                                        Anul chiriei
                                    </Label>
                                    <Input
                                        id="period_year"
                                        name="period_year"
                                        className={inputClassName}
                                        type="number"
                                        min="2000"
                                        max="2100"
                                        step="1"
                                        defaultValue={fieldValue(
                                            payment?.period_year ??
                                                now.getFullYear(),
                                        )}
                                        required
                                    />
                                    <InputError message={errors.period_year} />
                                </Field>
                            </>
                        ) : (
                            <>
                                <input name="period_month" type="hidden" />
                                <input name="period_year" type="hidden" />
                                <div className="text-xs text-muted-foreground md:col-span-2">
                                    <div className="space-y-1">
                                        <p>
                                            Garanție contract:{' '}
                                            {formatMoney(
                                                selectedLease?.deposit_amount,
                                                selectedLease?.currency,
                                            )}
                                        </p>
                                        <p>
                                            Încasat deja:{' '}
                                            {formatMoney(
                                                String(
                                                    collectedGuaranteeExcludingCurrent,
                                                ),
                                                selectedLease?.currency,
                                            )}
                                        </p>
                                        <p>
                                            Rămas de încasat:{' '}
                                            {formatMoney(
                                                String(remainingGuarantee),
                                                selectedLease?.currency,
                                            )}
                                        </p>
                                        {guaranteeIsBlocked ? (
                                            <p className="font-medium text-destructive">
                                                Acest contract nu mai are
                                                garanție de încasat.
                                            </p>
                                        ) : null}
                                    </div>
                                </div>
                            </>
                        )}
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
                                defaultValue={fieldValue(payment?.notes)}
                            />
                            <InputError message={errors.notes} />
                        </Field>
                    </section>

                    <div className="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                        <Button
                            type="submit"
                            disabled={
                                processing ||
                                leases.length === 0 ||
                                guaranteeIsBlocked
                            }
                            data-test="payment-save-button"
                        >
                            {submitLabel}
                        </Button>
                    </div>
                </>
            )}
        </Form>
    );
}
