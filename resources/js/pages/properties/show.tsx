import { Head, Link, router, usePage } from '@inertiajs/react';
import { ArrowLeft, Pencil, Trash2 } from 'lucide-react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { useI18n } from '@/lib/i18n';
import { Button } from '@/components/ui/button';
import {
    propertyStatusLabel,
    propertyTypeLabel,
} from '@/pages/properties/labels';
import { destroy, edit, index, show } from '@/routes/properties';
import type { Property } from '@/types';

type Props = {
    property: Property;
};

function formatValue(value?: string | number | null) {
    return value ?? 'Nesetat';
}

function formatMoney(amount?: string | null, currency = 'RON') {
    if (!amount) {
        return 'Nesetat';
    }

    return `${Number(amount).toLocaleString(undefined, {
        maximumFractionDigits: 2,
        minimumFractionDigits: 0,
    })} ${currency}`;
}

function Detail({
    label,
    value,
}: {
    label: string;
    value?: string | number | null;
}) {
    return (
        <div>
            <dt className="text-sm text-muted-foreground">{label}</dt>
            <dd className="mt-1 text-sm font-medium">{formatValue(value)}</dd>
        </div>
    );
}

export default function PropertyShow({ property }: Props) {
    const { currentTeam } = usePage().props;
    const { t } = useI18n();
    const currentTeamSlug = currentTeam?.slug ?? '';

    const deleteProperty = () => {
        if (!window.confirm(`Ștergi ${property.name}?`)) {
            return;
        }

        router.delete(destroy([currentTeamSlug, property.id]).url);
    };

    return (
        <>
            <Head title={property.name} />

            <div className="mx-auto flex w-full max-w-[1180px] flex-col gap-5 p-3 sm:p-5 lg:p-6">
                <div className="flex flex-col gap-4 rounded-2xl border border-border/70 bg-card/75 p-4 shadow-sm sm:flex-row sm:items-start sm:justify-between sm:p-5">
                    <div className="space-y-2">
                        <Heading
                            variant="small"
                            title={property.name}
                            description={`${property.address_line}, ${property.city}`}
                        />
                        <Badge variant="secondary">
                            {propertyStatusLabel(property.status)}
                        </Badge>
                    </div>

                    <div className="flex flex-col-reverse gap-2 sm:flex-row">
                        <Button variant="outline" asChild>
                            <Link href={index(currentTeamSlug)}>
                                <ArrowLeft /> {t('common.back')}
                            </Link>
                        </Button>
                        <Button asChild>
                            <Link href={edit([currentTeamSlug, property.id])}>
                                <Pencil /> {t('common.edit')}
                            </Link>
                        </Button>
                        <Button
                            variant="destructive"
                            onClick={deleteProperty}
                            data-test="property-delete-button"
                        >
                            <Trash2 /> {t('common.delete')}
                        </Button>
                    </div>
                </div>

                <section className="rounded-2xl border border-border/70 bg-card/85 p-4 shadow-sm sm:p-5">
                    <h2 className="text-base font-medium">
                        {t('properties.section.main')}
                    </h2>
                    <dl className="mt-2.5 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                        <Detail
                            label={t('properties.field.type')}
                            value={propertyTypeLabel(property.type)}
                        />
                        <Detail
                            label={t('common.status')}
                            value={propertyStatusLabel(property.status)}
                        />
                        <Detail label={t('properties.field.country')} value={property.country} />
                    </dl>
                </section>

                <section className="rounded-2xl border border-border/70 bg-card/85 p-4 shadow-sm sm:p-5">
                    <h2 className="text-base font-medium">{t('properties.section.address')}</h2>
                    <dl className="mt-2.5 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                        <Detail label={t('properties.field.city')} value={property.city} />
                        <Detail
                            label={t('properties.field.county')}
                            value={property.county_or_sector}
                        />
                        <Detail
                            label={t('properties.field.postalCode')}
                            value={property.postal_code}
                        />
                        <Detail label={t('properties.field.address')} value={property.address_line} />
                    </dl>
                </section>

                <section className="rounded-2xl border border-border/70 bg-card/85 p-4 shadow-sm sm:p-5">
                    <h2 className="text-base font-medium">{t('properties.section.features')}</h2>
                    <dl className="mt-2.5 grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
                        <Detail label={t('properties.field.rooms')} value={property.rooms} />
                        <Detail
                            label="Suprafață utilă"
                            value={
                                property.usable_area_sqm
                                    ? `${property.usable_area_sqm} mp`
                                    : null
                            }
                        />
                        <Detail
                            label="Suprafață totală"
                            value={
                                property.total_area_sqm
                                    ? `${property.total_area_sqm} m²`
                                    : null
                            }
                        />
                        <Detail label={t('properties.field.floor')} value={property.floor} />
                        <Detail
                            label={t('properties.field.totalFloors')}
                            value={property.total_floors}
                        />
                    </dl>
                </section>

                <section className="rounded-2xl border border-border/70 bg-card/85 p-4 shadow-sm sm:p-5">
                    <h2 className="text-base font-medium">{t('properties.section.rent')}</h2>
                    <dl className="mt-2.5 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                        <Detail
                            label={t('properties.field.monthlyRent')}
                            value={formatMoney(
                                property.monthly_rent_amount,
                                property.currency,
                            )}
                        />
                        <Detail label={t('common.currency')} value={property.currency} />
                        <Detail
                            label={t('properties.field.deposit')}
                            value={formatMoney(
                                property.deposit_amount,
                                property.currency,
                            )}
                        />
                    </dl>
                    {property.active_contract_guarantee_notice ? (
                        <div className="mt-3 rounded-md border border-amber-200 bg-amber-50 p-2.5 text-sm text-amber-900">
                            <p className="font-medium">
                                {
                                    property.active_contract_guarantee_notice
                                        .message
                                }
                            </p>
                            <div className="mt-1 grid gap-1 text-xs sm:grid-cols-2">
                                {property.active_contract_guarantee_notice
                                    .property_guarantee ? (
                                    <span>
                                        Garanție informativă proprietate:{' '}
                                        {formatMoney(
                                            property
                                                .active_contract_guarantee_notice
                                                .property_guarantee,
                                            property.currency,
                                        )}
                                    </span>
                                ) : null}
                                <span>
                                    Garanție contract activ:{' '}
                                    {formatMoney(
                                        property
                                            .active_contract_guarantee_notice
                                            .contract_guarantee,
                                        property.currency,
                                    )}
                                </span>
                            </div>
                        </div>
                    ) : null}
                </section>

                {property.notes ? (
                    <section className="rounded-2xl border border-border/70 bg-card/85 p-4 shadow-sm sm:p-5">
                        <h2 className="text-base font-medium">{t('properties.section.notes')}</h2>
                        <p className="mt-2.5 text-sm whitespace-pre-wrap">
                            {property.notes}
                        </p>
                    </section>
                ) : null}
            </div>
        </>
    );
}

PropertyShow.layout = (props: {
    currentTeam?: { slug: string } | null;
    property: Property;
}) => ({
    breadcrumbs: [
        {
            title: 'Proprietăți',
            href: props.currentTeam ? index(props.currentTeam.slug) : '/',
        },
        {
            title: props.property.name,
            href: props.currentTeam
                ? show([props.currentTeam.slug, props.property.id])
                : '/',
        },
    ],
});
