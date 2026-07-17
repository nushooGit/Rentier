import { Head, Link, router, usePage } from '@inertiajs/react';
import { ArrowLeft, Pencil, Trash2 } from 'lucide-react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
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

            <div className="mx-auto flex w-full max-w-6xl flex-col space-y-3.5 p-3 sm:p-4">
                <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
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
                                <ArrowLeft /> Înapoi
                            </Link>
                        </Button>
                        <Button asChild>
                            <Link href={edit([currentTeamSlug, property.id])}>
                                <Pencil /> Editează
                            </Link>
                        </Button>
                        <Button
                            variant="destructive"
                            onClick={deleteProperty}
                            data-test="property-delete-button"
                        >
                            <Trash2 /> Șterge
                        </Button>
                    </div>
                </div>

                <section className="rounded-lg border p-3 sm:p-3.5">
                    <h2 className="text-base font-medium">
                        Detalii principale
                    </h2>
                    <dl className="mt-2.5 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                        <Detail
                            label="Tip"
                            value={propertyTypeLabel(property.type)}
                        />
                        <Detail
                            label="Status"
                            value={propertyStatusLabel(property.status)}
                        />
                        <Detail label="Țară" value={property.country} />
                    </dl>
                </section>

                <section className="rounded-lg border p-3 sm:p-3.5">
                    <h2 className="text-base font-medium">Adresă</h2>
                    <dl className="mt-2.5 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                        <Detail label="Oraș" value={property.city} />
                        <Detail
                            label="Județ / Sector"
                            value={property.county_or_sector}
                        />
                        <Detail
                            label="Cod poștal"
                            value={property.postal_code}
                        />
                        <Detail label="Adresă" value={property.address_line} />
                    </dl>
                </section>

                <section className="rounded-lg border p-3 sm:p-3.5">
                    <h2 className="text-base font-medium">Caracteristici</h2>
                    <dl className="mt-2.5 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                        <Detail label="Camere" value={property.rooms} />
                        <Detail
                            label="Suprafață utilă"
                            value={
                                property.usable_area_sqm
                                    ? `${property.usable_area_sqm} mp`
                                    : null
                            }
                        />
                        <Detail label="Etaj" value={property.floor} />
                        <Detail
                            label="Total etaje"
                            value={property.total_floors}
                        />
                    </dl>
                </section>

                <section className="rounded-lg border p-3 sm:p-3.5">
                    <h2 className="text-base font-medium">Setări chirie</h2>
                    <dl className="mt-2.5 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                        <Detail
                            label="Chirie lunară"
                            value={formatMoney(
                                property.monthly_rent_amount,
                                property.currency,
                            )}
                        />
                        <Detail label="Monedă" value={property.currency} />
                        <Detail
                            label="Garanție"
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
                    <section className="rounded-lg border p-3 sm:p-3.5">
                        <h2 className="text-base font-medium">Note interne</h2>
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
