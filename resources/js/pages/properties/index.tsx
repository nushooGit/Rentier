import { Head, Link, router, usePage } from '@inertiajs/react';
import { Eye, Pencil, Plus, Trash2 } from 'lucide-react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import {
    propertyStatusLabel,
    propertyTypeLabel,
} from '@/pages/properties/labels';
import { create, destroy, edit, index, show } from '@/routes/properties';
import type {
    Property,
    PropertyOption,
    PropertyStatus,
    PropertyType,
    RentPaymentStatusKey,
} from '@/types';

type Props = {
    properties: Property[];
    propertyTypes: PropertyOption<PropertyType>[];
    propertyStatuses: PropertyOption<PropertyStatus>[];
};

function formatMoney(amount?: string | null, currency = 'RON') {
    if (!amount) {
        return 'Chirie nesetată';
    }

    return `${Number(amount).toLocaleString(undefined, {
        maximumFractionDigits: 2,
        minimumFractionDigits: 0,
    })} ${currency}`;
}

function hasPositiveAmount(amount?: string | null) {
    return Number(amount ?? 0) > 0;
}

const rentStatusClassNames: Record<RentPaymentStatusKey, string> = {
    paid: 'border-emerald-200 bg-emerald-50 text-emerald-700',
    partial: 'border-amber-200 bg-amber-50 text-amber-700',
    due_today: 'border-sky-200 bg-sky-50 text-sky-700',
    upcoming: 'border-slate-200 bg-slate-50 text-slate-700',
    overdue: 'border-red-200 bg-red-50 text-red-700',
};

export default function PropertiesIndex({ properties }: Props) {
    const { currentTeam } = usePage().props;
    const currentTeamSlug = currentTeam?.slug ?? '';

    const deleteProperty = (property: Property) => {
        if (
            !window.confirm(
                'Sigur vrei să ștergi această proprietate? Acțiunea nu poate fi anulată.',
            )
        ) {
            return;
        }

        router.delete(destroy([currentTeamSlug, property.id]).url);
    };

    return (
        <>
            <Head title="Proprietăți" />

            <h1 className="sr-only">Proprietăți</h1>

            <div className="mx-auto flex w-full max-w-7xl flex-col space-y-3.5 p-3 sm:p-4">
                <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <Heading
                        variant="small"
                        title="Proprietăți"
                        description="Gestionează proprietățile din acest workspace"
                    />

                    <Button asChild data-test="property-create-link">
                        <Link href={create(currentTeamSlug)}>
                            <Plus /> Proprietate nouă
                        </Link>
                    </Button>
                </div>

                {properties.length > 0 ? (
                    <div className="grid gap-2.5 md:grid-cols-2 xl:grid-cols-3">
                        {properties.map((property) => (
                            <article
                                key={property.id}
                                className="flex flex-col rounded-lg border transition-colors focus-within:border-primary/30 hover:border-primary/30 hover:bg-muted/20"
                                data-test="property-card"
                            >
                                <Link
                                    href={show([currentTeamSlug, property.id])}
                                    className="flex flex-1 cursor-pointer flex-col gap-2.5 rounded-lg p-3 focus:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                                    data-test="property-card-link"
                                    aria-label={`Vezi proprietatea ${property.name}`}
                                >
                                    <div className="flex items-start justify-between gap-3">
                                        <div className="min-w-0">
                                            <h2 className="truncate text-base font-medium">
                                                {property.name}
                                            </h2>
                                            <p className="mt-1 text-sm text-muted-foreground">
                                                {propertyTypeLabel(
                                                    property.type,
                                                )}{' '}
                                                · {property.city}
                                                {property.county_or_sector
                                                    ? `, ${property.county_or_sector}`
                                                    : ''}
                                            </p>
                                        </div>
                                        <Badge variant="secondary">
                                            {propertyStatusLabel(
                                                property.status,
                                            )}
                                        </Badge>
                                    </div>

                                    <div className="grid gap-0.5 text-sm">
                                        <span className="font-medium">
                                            {formatMoney(
                                                property.monthly_rent_amount,
                                                property.currency,
                                            )}
                                        </span>
                                        <span className="text-muted-foreground">
                                            {property.address_line}
                                        </span>
                                        {property.rent_payment_status ? (
                                            <div className="mt-1 grid gap-1">
                                                <Badge
                                                    variant="outline"
                                                    className={`w-fit ${rentStatusClassNames[property.rent_payment_status.key]}`}
                                                >
                                                    {
                                                        property
                                                            .rent_payment_status
                                                            .label
                                                    }
                                                </Badge>
                                                {hasPositiveAmount(
                                                    property.rent_payment_status
                                                        .rent_deduction_amount,
                                                ) ? (
                                                    <span className="text-xs text-muted-foreground">
                                                        Scăzut din chirie:{' '}
                                                        {formatMoney(
                                                            property
                                                                .rent_payment_status
                                                                .rent_deduction_amount,
                                                            property.currency,
                                                        )}
                                                    </span>
                                                ) : null}
                                                {hasPositiveAmount(
                                                    property.rent_payment_status
                                                        .collected_amount,
                                                ) ? (
                                                    <span className="text-xs text-muted-foreground">
                                                        Încasat:{' '}
                                                        {formatMoney(
                                                            property
                                                                .rent_payment_status
                                                                .collected_amount,
                                                            property.currency,
                                                        )}
                                                    </span>
                                                ) : null}
                                            </div>
                                        ) : null}
                                    </div>
                                </Link>

                                <TooltipProvider>
                                    <div className="mt-auto flex justify-end gap-2 px-3 pb-3">
                                        <Tooltip>
                                            <TooltipTrigger asChild>
                                                <Button
                                                    variant="ghost"
                                                    size="sm"
                                                    asChild
                                                    data-test="property-view-link"
                                                >
                                                    <Link
                                                        href={show([
                                                            currentTeamSlug,
                                                            property.id,
                                                        ])}
                                                    >
                                                        <Eye className="h-4 w-4" />
                                                    </Link>
                                                </Button>
                                            </TooltipTrigger>
                                            <TooltipContent>
                                                <p>Vezi proprietatea</p>
                                            </TooltipContent>
                                        </Tooltip>

                                        <Tooltip>
                                            <TooltipTrigger asChild>
                                                <Button
                                                    variant="ghost"
                                                    size="sm"
                                                    asChild
                                                    data-test="property-edit-link"
                                                >
                                                    <Link
                                                        href={edit([
                                                            currentTeamSlug,
                                                            property.id,
                                                        ])}
                                                    >
                                                        <Pencil className="h-4 w-4" />
                                                    </Link>
                                                </Button>
                                            </TooltipTrigger>
                                            <TooltipContent>
                                                <p>Editează proprietatea</p>
                                            </TooltipContent>
                                        </Tooltip>

                                        <Tooltip>
                                            <TooltipTrigger asChild>
                                                <Button
                                                    variant="ghost"
                                                    size="sm"
                                                    type="button"
                                                    onClick={() =>
                                                        deleteProperty(property)
                                                    }
                                                    aria-label="Șterge proprietatea"
                                                    data-test="property-delete-button"
                                                >
                                                    <Trash2 className="h-4 w-4" />
                                                </Button>
                                            </TooltipTrigger>
                                            <TooltipContent>
                                                <p>Șterge proprietatea</p>
                                            </TooltipContent>
                                        </Tooltip>
                                    </div>
                                </TooltipProvider>
                            </article>
                        ))}
                    </div>
                ) : (
                    <div className="rounded-lg border border-dashed p-5 text-center sm:p-6">
                        <h2 className="text-base font-medium">
                            Nu există proprietăți încă
                        </h2>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Adaugă prima proprietate pentru a începe urmărirea
                            contractelor, chiriei, cheltuielilor și mentenanței.
                        </p>
                        <Button className="mt-4" asChild>
                            <Link href={create(currentTeamSlug)}>
                                <Plus /> Proprietate nouă
                            </Link>
                        </Button>
                    </div>
                )}
            </div>
        </>
    );
}

PropertiesIndex.layout = (props: {
    currentTeam?: { slug: string } | null;
}) => ({
    breadcrumbs: [
        {
            title: 'Proprietăți',
            href: props.currentTeam ? index(props.currentTeam.slug) : '/',
        },
    ],
});
