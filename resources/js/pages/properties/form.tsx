import { Form } from '@inertiajs/react';
import type { ReactNode } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    propertyStatusLabel,
    propertyTypeLabel,
} from '@/pages/properties/labels';
import type {
    Property,
    PropertyOption,
    PropertyStatus,
    PropertyType,
} from '@/types';

type Props = {
    action: {
        action: string;
        method: 'get' | 'post' | 'put' | 'patch' | 'delete';
    };
    submitLabel: string;
    property?: Property;
    propertyTypes: PropertyOption<PropertyType>[];
    propertyStatuses: PropertyOption<PropertyStatus>[];
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

function Section({ title, children }: { title: string; children: ReactNode }) {
    return (
        <FormSection
            title={title}
            gridClassName="md:grid-cols-2 xl:grid-cols-3"
        >
            {children}
        </FormSection>
    );
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

export default function PropertyForm({
    action,
    submitLabel,
    property,
    propertyTypes,
    propertyStatuses,
}: Props) {
    return (
        <Form {...action} className="space-y-3.5">
            {({ errors, processing }) => (
                <>
                    <Section title="Detalii principale">
                        <Field>
                            <Label htmlFor="name">Nume proprietate</Label>
                            <Input
                                id="name"
                                name="name"
                                className={inputClassName}
                                defaultValue={fieldValue(property?.name)}
                                required
                                data-test="property-name-input"
                            />
                            <InputError message={errors.name} />
                        </Field>

                        <Field>
                            <Label htmlFor="type">Tip</Label>
                            <select
                                id="type"
                                name="type"
                                className={selectClassName}
                                defaultValue={property?.type ?? 'apartment'}
                                required
                                data-test="property-type-select"
                            >
                                {propertyTypes.map((type) => (
                                    <option key={type.value} value={type.value}>
                                        {propertyTypeLabel(type.value)}
                                    </option>
                                ))}
                            </select>
                            <InputError message={errors.type} />
                        </Field>

                        <Field>
                            <Label htmlFor="status">Status</Label>
                            <select
                                id="status"
                                name="status"
                                className={selectClassName}
                                defaultValue={property?.status ?? 'available'}
                                required
                                data-test="property-status-select"
                            >
                                {propertyStatuses.map((status) => (
                                    <option
                                        key={status.value}
                                        value={status.value}
                                    >
                                        {propertyStatusLabel(status.value)}
                                    </option>
                                ))}
                            </select>
                            <InputError message={errors.status} />
                        </Field>
                    </Section>

                    <FormSection
                        title="Adresă"
                        gridClassName="md:grid-cols-2 xl:grid-cols-4"
                    >
                        <Field>
                            <Label htmlFor="country">Țară</Label>
                            <Input
                                id="country"
                                name="country"
                                className={inputClassName}
                                defaultValue={fieldValue(
                                    property?.country ?? 'Romania',
                                )}
                            />
                            <InputError message={errors.country} />
                        </Field>

                        <Field>
                            <Label htmlFor="city">Oraș</Label>
                            <Input
                                id="city"
                                name="city"
                                className={inputClassName}
                                defaultValue={fieldValue(property?.city)}
                                required
                                data-test="property-city-input"
                            />
                            <InputError message={errors.city} />
                        </Field>

                        <Field>
                            <Label htmlFor="county_or_sector">
                                Județ / Sector
                            </Label>
                            <Input
                                id="county_or_sector"
                                name="county_or_sector"
                                className={inputClassName}
                                defaultValue={fieldValue(
                                    property?.county_or_sector,
                                )}
                            />
                            <InputError message={errors.county_or_sector} />
                        </Field>

                        <Field>
                            <Label htmlFor="postal_code">Cod poștal</Label>
                            <Input
                                id="postal_code"
                                name="postal_code"
                                className={inputClassName}
                                defaultValue={fieldValue(property?.postal_code)}
                            />
                            <InputError message={errors.postal_code} />
                        </Field>

                        <Field className="md:col-span-2 xl:col-span-4">
                            <Label htmlFor="address_line">Adresă</Label>
                            <Input
                                id="address_line"
                                name="address_line"
                                className={inputClassName}
                                defaultValue={fieldValue(
                                    property?.address_line,
                                )}
                                required
                                data-test="property-address-input"
                            />
                            <InputError message={errors.address_line} />
                        </Field>
                    </FormSection>

                    <FormSection
                        title="Caracteristici"
                        gridClassName="md:grid-cols-2 xl:grid-cols-4"
                    >
                        <Field>
                            <Label htmlFor="rooms">Camere</Label>
                            <Input
                                id="rooms"
                                name="rooms"
                                className={inputClassName}
                                type="number"
                                min="0"
                                step="1"
                                defaultValue={fieldValue(property?.rooms)}
                            />
                            <InputError message={errors.rooms} />
                        </Field>

                        <Field>
                            <Label htmlFor="usable_area_sqm">
                                Suprafață utilă, mp
                            </Label>
                            <Input
                                id="usable_area_sqm"
                                name="usable_area_sqm"
                                className={inputClassName}
                                type="number"
                                min="0"
                                step="0.01"
                                defaultValue={fieldValue(
                                    property?.usable_area_sqm,
                                )}
                            />
                            <InputError message={errors.usable_area_sqm} />
                        </Field>

                        <Field>
                            <Label htmlFor="floor">Etaj</Label>
                            <Input
                                id="floor"
                                name="floor"
                                className={inputClassName}
                                type="number"
                                step="1"
                                defaultValue={fieldValue(property?.floor)}
                            />
                            <InputError message={errors.floor} />
                        </Field>

                        <Field>
                            <Label htmlFor="total_floors">Total etaje</Label>
                            <Input
                                id="total_floors"
                                name="total_floors"
                                className={inputClassName}
                                type="number"
                                min="0"
                                step="1"
                                defaultValue={fieldValue(
                                    property?.total_floors,
                                )}
                            />
                            <InputError message={errors.total_floors} />
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
                                defaultValue={fieldValue(
                                    property?.monthly_rent_amount,
                                )}
                            />
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
                                    property?.currency ?? 'RON',
                                )}
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
                                defaultValue={fieldValue(
                                    property?.rent_due_day,
                                )}
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
                                defaultValue={fieldValue(
                                    property?.deposit_amount,
                                )}
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
                                defaultValue={fieldValue(property?.notes)}
                            />
                            <InputError message={errors.notes} />
                        </Field>
                    </section>

                    <div className="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                        <Button
                            type="submit"
                            disabled={processing}
                            data-test="property-save-button"
                        >
                            {submitLabel}
                        </Button>
                    </div>
                </>
            )}
        </Form>
    );
}
