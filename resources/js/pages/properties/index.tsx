import { Head, Link, usePage } from '@inertiajs/react';
import { Eye, Pencil, Plus } from 'lucide-react';
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
import { create, edit, index, show } from '@/routes/properties';
import type {
    Property,
    PropertyOption,
    PropertyStatus,
    PropertyType,
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

export default function PropertiesIndex({ properties }: Props) {
    const { currentTeam } = usePage().props;
    const currentTeamSlug = currentTeam?.slug ?? '';

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
                                className="flex flex-col gap-2.5 rounded-lg border p-3"
                                data-test="property-card"
                            >
                                <div className="flex items-start justify-between gap-3">
                                    <div className="min-w-0">
                                        <h2 className="truncate text-base font-medium">
                                            {property.name}
                                        </h2>
                                        <p className="mt-1 text-sm text-muted-foreground">
                                            {propertyTypeLabel(property.type)} ·{' '}
                                            {property.city}
                                            {property.county_or_sector
                                                ? `, ${property.county_or_sector}`
                                                : ''}
                                        </p>
                                    </div>
                                    <Badge variant="secondary">
                                        {propertyStatusLabel(property.status)}
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
                                </div>

                                <TooltipProvider>
                                    <div className="mt-auto flex justify-end gap-2">
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
