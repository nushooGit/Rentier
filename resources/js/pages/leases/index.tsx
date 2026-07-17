import { Head, Link, router, usePage } from '@inertiajs/react';
import { Eye, FileText, Pencil, Plus, Trash2 } from 'lucide-react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { formatDateRangeLong } from '@/lib/date';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { leaseStatusLabel } from '@/pages/leases/labels';
import { create, destroy, edit, index, show } from '@/routes/leases';
import type { Lease, LeaseOption, LeaseStatus } from '@/types';

type Props = {
    leases: Lease[];
    leaseStatuses: LeaseOption<LeaseStatus>[];
};

function formatMoney(amount?: string | null, currency = 'RON') {
    if (!amount) {
        return 'Nesetat';
    }

    return `${Number(amount).toLocaleString(undefined, {
        maximumFractionDigits: 2,
        minimumFractionDigits: 0,
    })} ${currency}`;
}

function formatPeriod(lease: Lease) {
    return formatDateRangeLong(lease.start_date, lease.end_date);
}

export default function LeasesIndex({ leases }: Props) {
    const { currentTeam } = usePage().props;
    const currentTeamSlug = currentTeam?.slug ?? '';

    const deleteLease = (lease: Lease) => {
        if (
            !window.confirm(
                'Sigur vrei să ștergi acest contract? Acțiunea nu poate fi anulată.',
            )
        ) {
            return;
        }

        router.delete(destroy([currentTeamSlug, lease.id]).url);
    };

    return (
        <>
            <Head title="Contracte" />

            <h1 className="sr-only">Contracte</h1>

            <div className="mx-auto flex w-full max-w-7xl flex-col space-y-3.5 p-3 sm:p-4">
                <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <Heading
                        variant="small"
                        title="Contracte"
                        description="Gestionează contractele și chiriașii din acest workspace"
                    />

                    <Button asChild data-test="lease-create-link">
                        <Link href={create(currentTeamSlug)}>
                            <Plus /> Contract nou
                        </Link>
                    </Button>
                </div>

                {leases.length > 0 ? (
                    <div className="grid gap-2.5 md:grid-cols-2 xl:grid-cols-3">
                        {leases.map((lease) => (
                            <article
                                key={lease.id}
                                className="flex flex-col rounded-lg border transition-colors focus-within:border-primary/30 hover:border-primary/30 hover:bg-muted/20"
                                data-test="lease-card"
                            >
                                <Link
                                    href={show([currentTeamSlug, lease.id])}
                                    className="flex flex-1 cursor-pointer flex-col gap-2.5 rounded-lg p-3 focus:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                                    data-test="lease-card-link"
                                    aria-label={`Vezi contractul pentru ${lease.renter.name}`}
                                >
                                    <div className="flex items-start justify-between gap-3">
                                        <div className="min-w-0">
                                            <h2 className="truncate text-base font-medium">
                                                {lease.renter.name}
                                            </h2>
                                            <p className="mt-1 text-sm text-muted-foreground">
                                                {lease.property.name} ·{' '}
                                                {lease.property.city}
                                            </p>
                                        </div>
                                        <Badge variant="secondary">
                                            {leaseStatusLabel(lease.status)}
                                        </Badge>
                                    </div>

                                    <div className="grid gap-0.5 text-sm">
                                        <span className="font-medium">
                                            {formatMoney(
                                                lease.monthly_rent_amount,
                                                lease.currency,
                                            )}
                                        </span>
                                        <span className="text-muted-foreground">
                                            {formatPeriod(lease)}
                                        </span>
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
                                                    data-test="lease-view-link"
                                                >
                                                    <Link
                                                        href={show([
                                                            currentTeamSlug,
                                                            lease.id,
                                                        ])}
                                                    >
                                                        <Eye className="h-4 w-4" />
                                                    </Link>
                                                </Button>
                                            </TooltipTrigger>
                                            <TooltipContent>
                                                <p>Vezi contractul</p>
                                            </TooltipContent>
                                        </Tooltip>

                                        <Tooltip>
                                            <TooltipTrigger asChild>
                                                <Button
                                                    variant="ghost"
                                                    size="sm"
                                                    asChild
                                                    data-test="lease-edit-link"
                                                >
                                                    <Link
                                                        href={edit([
                                                            currentTeamSlug,
                                                            lease.id,
                                                        ])}
                                                    >
                                                        <Pencil className="h-4 w-4" />
                                                    </Link>
                                                </Button>
                                            </TooltipTrigger>
                                            <TooltipContent>
                                                <p>Editează contractul</p>
                                            </TooltipContent>
                                        </Tooltip>

                                        <Tooltip>
                                            <TooltipTrigger asChild>
                                                <Button
                                                    variant="ghost"
                                                    size="sm"
                                                    type="button"
                                                    onClick={() =>
                                                        deleteLease(lease)
                                                    }
                                                    aria-label="Șterge contractul"
                                                    data-test="lease-delete-button"
                                                >
                                                    <Trash2 className="h-4 w-4" />
                                                </Button>
                                            </TooltipTrigger>
                                            <TooltipContent>
                                                <p>Șterge contractul</p>
                                            </TooltipContent>
                                        </Tooltip>
                                    </div>
                                </TooltipProvider>
                            </article>
                        ))}
                    </div>
                ) : (
                    <div className="rounded-lg border border-dashed p-5 text-center sm:p-6">
                        <FileText className="mx-auto h-8 w-8 text-muted-foreground" />
                        <h2 className="mt-3 text-base font-medium">
                            Nu există contracte încă
                        </h2>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Creează primul contract și adaugă datele de contact
                            ale chiriașului.
                        </p>
                        <Button className="mt-4" asChild>
                            <Link href={create(currentTeamSlug)}>
                                <Plus /> Contract nou
                            </Link>
                        </Button>
                    </div>
                )}
            </div>
        </>
    );
}

LeasesIndex.layout = (props: { currentTeam?: { slug: string } | null }) => ({
    breadcrumbs: [
        {
            title: 'Contracte',
            href: props.currentTeam ? index(props.currentTeam.slug) : '/',
        },
    ],
});
