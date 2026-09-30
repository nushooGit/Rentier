import { Head, Link, router, usePage } from '@inertiajs/react';
import { Eye, FileText, Pencil, Plus, Trash2 } from 'lucide-react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { formatDateRangeLong } from '@/lib/date';
import { translateKey, useI18n } from '@/lib/i18n';
import { leaseStatusLabel } from '@/pages/leases/labels';
import { create, destroy, edit, index, show } from '@/routes/leases';
import type { Lease, LeaseOption, LeaseStatus } from '@/types';

type Props = {
    leases: Lease[];
    leaseStatuses: LeaseOption<LeaseStatus>[];
};

function formatMoney(amount?: string | null, currency = 'RON') {
    if (!amount) {
        return translateKey('common.notSet');
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
    const { t } = useI18n();
    const currentTeamSlug = currentTeam?.slug ?? '';

    const deleteLease = (lease: Lease) => {
        if (!window.confirm(t('leases.index.deleteConfirm'))) {
            return;
        }

        router.delete(destroy([currentTeamSlug, lease.id]).url);
    };

    return (
        <>
            <Head title={t('nav.leases')} />

            <h1 className="sr-only">{t('nav.leases')}</h1>

            <div className="mx-auto flex w-full max-w-[1480px] flex-col gap-5 p-3 sm:p-5 lg:p-6">
                <div className="flex flex-col gap-4 rounded-2xl border border-border/70 bg-card/75 p-4 shadow-sm sm:flex-row sm:items-center sm:justify-between sm:p-5">
                    <Heading
                        variant="small"
                        title={t('nav.leases')}
                        description={t('leases.index.description')}
                    />

                    <Button asChild data-test="lease-create-link">
                        <Link href={create(currentTeamSlug)}>
                            <Plus /> {t('leases.index.new')}
                        </Link>
                    </Button>
                </div>

                {leases.length > 0 ? (
                    <div className="grid gap-3.5 md:grid-cols-2 xl:grid-cols-3">
                        {leases.map((lease) => (
                            <article
                                key={lease.id}
                                className="flex flex-col overflow-hidden rounded-2xl border border-border/70 bg-card/90 shadow-sm transition-all focus-within:border-primary/35 hover:-translate-y-0.5 hover:border-primary/30 hover:shadow-md"
                                data-test="lease-card"
                            >
                                <Link
                                    href={show([currentTeamSlug, lease.id])}
                                    className="flex flex-1 cursor-pointer flex-col gap-3 rounded-2xl p-4 focus:outline-none focus-visible:ring-2 focus-visible:ring-ring sm:p-5"
                                    data-test="lease-card-link"
                                    aria-label={t('leases.index.view', { name: lease.renter.name })}
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
                                    <div className="mt-auto flex justify-end gap-1.5 border-t border-border/60 bg-muted/20 px-3 py-2.5">
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
                                                <p>{t('leases.index.edit')}</p>
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
                                                    aria-label={t('leases.index.delete')}
                                                    data-test="lease-delete-button"
                                                >
                                                    <Trash2 className="h-4 w-4" />
                                                </Button>
                                            </TooltipTrigger>
                                            <TooltipContent>
                                                <p>{t('leases.index.delete')}</p>
                                            </TooltipContent>
                                        </Tooltip>
                                    </div>
                                </TooltipProvider>
                            </article>
                        ))}
                    </div>
                ) : (
                    <div className="rounded-2xl border border-dashed border-border bg-card/50 p-8 text-center shadow-sm">
                        <FileText className="mx-auto h-8 w-8 text-muted-foreground" />
                        <h2 className="mt-3 text-base font-medium">
                            {t('leases.index.emptyCurrent')}
                        </h2>
                        <p className="mt-1 text-sm text-muted-foreground">
                            {t('leases.index.emptyCurrentDescription')}
                        </p>
                        <Button className="mt-4" asChild>
                            <Link href={create(currentTeamSlug)}>
                                <Plus /> {t('leases.index.new')}
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
            title: translateKey('nav.leases'),
            href: props.currentTeam ? index(props.currentTeam.slug) : '/',
        },
    ],
});
