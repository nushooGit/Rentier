import { Head, Link, router, usePage } from '@inertiajs/react';
import { ArrowLeft, Pencil, Trash2 } from 'lucide-react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { translateKey, useI18n } from '@/lib/i18n';
import { currentIntlLocale } from '@/lib/locale';
import { Button } from '@/components/ui/button';
import { formatDateLong } from '@/lib/date';
import { leaseStatusLabel } from '@/pages/leases/labels';
import { destroy, edit, index, show } from '@/routes/leases';
import type { Lease } from '@/types';

type Props = {
    lease: Lease & { renter: Lease['renter'] & { hasPortalAccount?: boolean } };
};

function formatValue(value?: string | number | null) {
    return value ?? translateKey('common.notSet');
}

function formatMoney(amount?: string | null, currency = 'RON') {
    if (!amount) {
        return translateKey('common.notSet');
    }

    return `${Number(amount).toLocaleString(currentIntlLocale(), {
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

export default function LeaseShow({ lease }: Props) {
    const { currentTeam } = usePage().props;
    const { t } = useI18n();
    const currentTeamSlug = currentTeam?.slug ?? '';

    const deleteLease = () => {
        if (
            !window.confirm(
                t('leases.show.deleteConfirm', { name: lease.renter.name }),
            )
        ) {
            return;
        }

        router.delete(destroy([currentTeamSlug, lease.id]).url);
    };

    return (
        <>
            <Head title={t('leases.show.head', { name: lease.renter.name })} />

            <div className="mx-auto flex w-full max-w-[1180px] flex-col gap-5 p-3 sm:p-5 lg:p-6">
                <div className="flex flex-col gap-4 rounded-2xl border border-border/70 bg-card/75 p-4 shadow-sm sm:flex-row sm:items-start sm:justify-between sm:p-5">
                    <div className="space-y-2">
                        <Heading
                            variant="small"
                            title={lease.renter.name}
                            description={`${lease.property.name}, ${lease.property.city}`}
                        />
                        <Badge variant="secondary">
                            {leaseStatusLabel(lease.status)}
                        </Badge>
                    </div>

                    <div className="flex flex-col-reverse gap-2 sm:flex-row">
                        <Button variant="outline" asChild>
                            <Link href={index(currentTeamSlug)}>
                                <ArrowLeft /> {t('common.back')}
                            </Link>
                        </Button>
                        <Button asChild>
                            <Link href={edit([currentTeamSlug, lease.id])}>
                                <Pencil /> {t('common.edit')}
                            </Link>
                        </Button>
                        <Button
                            variant="destructive"
                            onClick={deleteLease}
                            data-test="lease-delete-button"
                        >
                            <Trash2 /> {t('common.delete')}
                        </Button>
                    </div>
                </div>

                <section className="rounded-2xl border border-border/70 bg-card/85 p-4 shadow-sm sm:p-5">
                    <h2 className="text-base font-medium">{t('leases.section.details')}</h2>
                    <dl className="mt-2.5 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                        <Detail
                            label={t('common.property')}
                            value={lease.property.name}
                        />
                        <Detail
                            label={t('common.status')}
                            value={leaseStatusLabel(lease.status)}
                        />
                        <Detail
                            label={t('leases.field.startDate')}
                            value={formatDateLong(lease.start_date)}
                        />
                        <Detail
                            label={t('leases.field.endDate')}
                            value={formatDateLong(lease.end_date)}
                        />
                    </dl>
                </section>

                <section className="rounded-2xl border border-border/70 bg-card/85 p-4 shadow-sm sm:p-5">
                    <h2 className="text-base font-medium">{t('leases.section.renter')}</h2>
                    <dl className="mt-2.5 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                        <Detail
                            label={t('leases.field.renterName')}
                            value={lease.renter.name}
                        />
                        <Detail
                            label={t('leases.field.renterEmail')}
                            value={lease.renter.email}
                        />
                        <Detail
                            label={t('leases.field.renterPhone')}
                            value={lease.renter.phone}
                        />
                    </dl>
                    {lease.renter.email && !lease.renter.hasPortalAccount ? (
                        <div className="mt-4">
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => router.post(`/${encodeURIComponent(currentTeamSlug)}/renters/${lease.renter.id}/invitations`)}
                            >
                                {currentIntlLocale().startsWith('en') ? 'Send renter portal invitation' : 'Trimite invitație în portalul chiriașului'}
                            </Button>
                        </div>
                    ) : null}
                    {lease.renter.hasPortalAccount ? (
                        <p className="mt-3 text-sm text-muted-foreground">
                            {currentIntlLocale().startsWith('en') ? 'Renter portal account linked' : 'Cont de chiriaș asociat'}
                        </p>
                    ) : null}
                    {lease.renter.notes ? (
                        <p className="mt-3 text-sm whitespace-pre-wrap">
                            {lease.renter.notes}
                        </p>
                    ) : null}
                </section>

                <section className="rounded-2xl border border-border/70 bg-card/85 p-4 shadow-sm sm:p-5">
                    <h2 className="text-base font-medium">{t('leases.section.rent')}</h2>
                    <dl className="mt-2.5 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                        <Detail
                            label={t('properties.field.monthlyRent')}
                            value={formatMoney(
                                lease.monthly_rent_amount,
                                lease.currency,
                            )}
                        />
                        <Detail label={t('common.currency')} value={lease.currency} />
                        <Detail
                            label={t('leases.field.dueDay')}
                            value={lease.rent_due_day}
                        />
                        <Detail
                            label={t('leases.field.deposit')}
                            value={formatMoney(
                                lease.deposit_amount,
                                lease.currency,
                            )}
                        />
                    </dl>
                </section>

                {lease.notes ? (
                    <section className="rounded-2xl border border-border/70 bg-card/85 p-4 shadow-sm sm:p-5">
                        <h2 className="text-base font-medium">{t('leases.section.notes')}</h2>
                        <p className="mt-2.5 text-sm whitespace-pre-wrap">
                            {lease.notes}
                        </p>
                    </section>
                ) : null}
            </div>
        </>
    );
}

LeaseShow.layout = (props: {
    currentTeam?: { slug: string } | null;
    lease: Lease;
}) => ({
    breadcrumbs: [
        {
            title: translateKey('nav.leases'),
            href: props.currentTeam ? index(props.currentTeam.slug) : '/',
        },
        {
            title: props.lease.renter.name,
            href: props.currentTeam
                ? show([props.currentTeam.slug, props.lease.id])
                : '/',
        },
    ],
});
