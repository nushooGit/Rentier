import { Form } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import type { ReactNode } from 'react';
import DateInput from '@/components/date-input';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { Lease, LeasePropertyOption } from '@/types';

type Props = {
    action: {
        action: string;
        method: 'get' | 'post' | 'put' | 'patch' | 'delete';
    };
    submitLabel: string;
    lease?: Lease;
    properties: LeasePropertyOption[];
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
    gridClassName = 'md:grid-cols-2 xl:grid-cols-3',
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

export default function LeaseForm({
    action,
    submitLabel,
    lease,
    properties,
}: Props) {
    const [selectedPropertyId, setSelectedPropertyId] = useState(
        fieldValue(lease?.property_id).toString(),
    );
    const selectedProperty = useMemo(
        () =>
            properties.find(
                (property) => property.id.toString() === selectedPropertyId,
            ),
        [properties, selectedPropertyId],
    );
    const selectedPropertyRent = fieldValue(
        selectedProperty?.monthly_rent_amount,
    );

    return (
        <Form {...action} className="space-y-3.5">
            {({ errors, processing }) => (
                <>
                    <FormSection
                        title="Detalii contract"
                        gridClassName="md:grid-cols-2 xl:grid-cols-4"
                    >
                        <Field className="md:col-span-2">
                            <Label htmlFor="property_id">Proprietate</Label>
                            <select
                                id="property_id"
                                name="property_id"
                                className={selectClassName}
                                value={selectedPropertyId}
                                onChange={(event) =>
                                    setSelectedPropertyId(event.target.value)
                                }
                                required
                                data-test="lease-property-select"
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

                        <Field>
                            <Label htmlFor="start_date">Data început</Label>
                            <DateInput
                                id="start_date"
                                name="start_date"
                                className={inputClassName}
                                defaultValue={lease?.start_date}
                                required
                                data-test="lease-start-date-input"
                            />
                            <InputError message={errors.start_date} />
                        </Field>

                        <Field>
                            <Label htmlFor="end_date">Data sfârșit</Label>
                            <DateInput
                                id="end_date"
                                name="end_date"
                                className={inputClassName}
                                defaultValue={lease?.end_date}
                            />
                            <InputError message={errors.end_date} />
                        </Field>
                    </FormSection>

                    <FormSection
                        title="Chiriaș"
                        gridClassName="md:grid-cols-2 xl:grid-cols-3"
                    >
                        <Field>
                            <Label htmlFor="renter_name">Nume chiriaș</Label>
                            <Input
                                id="renter_name"
                                name="renter_name"
                                className={inputClassName}
                                defaultValue={fieldValue(lease?.renter.name)}
                                required
                                data-test="lease-renter-name-input"
                            />
                            <InputError message={errors.renter_name} />
                        </Field>

                        <Field>
                            <Label htmlFor="renter_email">Email chiriaș</Label>
                            <Input
                                id="renter_email"
                                name="renter_email"
                                className={inputClassName}
                                type="email"
                                defaultValue={fieldValue(lease?.renter.email)}
                            />
                            <InputError message={errors.renter_email} />
                        </Field>

                        <Field>
                            <Label htmlFor="renter_phone">
                                Telefon chiriaș
                            </Label>
                            <Input
                                id="renter_phone"
                                name="renter_phone"
                                className={inputClassName}
                                defaultValue={fieldValue(lease?.renter.phone)}
                            />
                            <InputError message={errors.renter_phone} />
                        </Field>

                        <Field className="md:col-span-2 xl:col-span-3">
                            <Label htmlFor="renter_notes">Note chiriaș</Label>
                            <textarea
                                id="renter_notes"
                                name="renter_notes"
                                className={textareaClassName}
                                rows={3}
                                defaultValue={fieldValue(lease?.renter.notes)}
                            />
                            <InputError message={errors.renter_notes} />
                        </Field>
                    </FormSection>

                    <FormSection
                        title="Setări chirie"
                        gridClassName="md:grid-cols-2 xl:grid-cols-4"
                    >
                        <Field>
                            <Label htmlFor="monthly_rent_amount">
                                Chirie lunară
                            </Label>
                            <Input
                                id="monthly_rent_amount"
                                name="monthly_rent_amount"
                                className={inputClassName}
                                type="number"
                                min="0"
                                step="0.01"
                                value={selectedPropertyRent}
                                readOnly
                                required
                                aria-describedby="monthly_rent_amount_help"
                                data-test="lease-monthly-rent-input"
                            />
                            <p
                                id="monthly_rent_amount_help"
                                className="text-xs text-muted-foreground"
                            >
                                Chiria este preluată automat din proprietatea
                                selectată.
                            </p>
                            <InputError message={errors.monthly_rent_amount} />
                        </Field>

                        <Field>
                            <Label htmlFor="currency">Monedă</Label>
                            <Input
                                id="currency"
                                name="currency"
                                className={inputClassName}
                                maxLength={3}
                                defaultValue={fieldValue(
                                    lease?.currency ?? 'RON',
                                )}
                                required
                            />
                            <InputError message={errors.currency} />
                        </Field>

                        <Field>
                            <Label htmlFor="rent_due_day">Zi scadență</Label>
                            <Input
                                id="rent_due_day"
                                name="rent_due_day"
                                className={inputClassName}
                                type="number"
                                min="1"
                                max="31"
                                step="1"
                                defaultValue={fieldValue(lease?.rent_due_day)}
                            />
                            <InputError message={errors.rent_due_day} />
                        </Field>

                        <Field>
                            <Label htmlFor="deposit_amount">Garanție</Label>
                            <Input
                                id="deposit_amount"
                                name="deposit_amount"
                                className={inputClassName}
                                type="number"
                                min="0"
                                step="0.01"
                                defaultValue={fieldValue(lease?.deposit_amount)}
                            />
                            <InputError message={errors.deposit_amount} />
                        </Field>
                    </FormSection>

                    <section className="space-y-2.5 rounded-lg border p-3 sm:p-3.5">
                        <h2 className="text-sm font-medium sm:text-base">
                            Note interne
                        </h2>
                        <Field>
                            <Label htmlFor="notes">Note</Label>
                            <textarea
                                id="notes"
                                name="notes"
                                className={textareaClassName}
                                rows={5}
                                defaultValue={fieldValue(lease?.notes)}
                            />
                            <InputError message={errors.notes} />
                        </Field>
                    </section>

                    <div className="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                        <Button
                            type="submit"
                            disabled={processing || properties.length === 0}
                            data-test="lease-save-button"
                        >
                            {submitLabel}
                        </Button>
                    </div>
                </>
            )}
        </Form>
    );
}
