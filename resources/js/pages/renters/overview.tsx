import { Head } from '@inertiajs/react';
import { formatDateShort } from '@/lib/date';
import { useI18n } from '@/lib/i18n';
import { currentIntlLocale } from '@/lib/locale';
import { leaseStatusLabel } from '@/pages/leases/labels';
import type { LeaseStatus } from '@/types';

type Lease = {
    id: number;
    property: string;
    startDate: string;
    endDate: string | null;
    rent: string;
    currency: string;
    dueDay: number | null;
    status: LeaseStatus;
};

export default function RenterOverview({ leases }: { leases: Lease[] }) {
    const { t } = useI18n();

    return (
        <main className="mx-auto min-h-screen max-w-3xl space-y-6 p-6">
            <Head title={t('renter.overview.head')} />
            <h1 className="text-2xl font-semibold">{t('renter.overview.title')}</h1>
            <p className="text-sm text-muted-foreground">
                {t('renter.overview.description')}
            </p>
            {leases.length === 0 ? (
                <p>{t('renter.overview.empty')}</p>
            ) : (
                <div className="grid gap-4">
                    {leases.map((lease) => (
                        <section key={lease.id} className="rounded-xl border p-5">
                            <h2 className="font-semibold">{lease.property}</h2>
                            <p>
                                {t('renter.overview.rent')}: {Number(lease.rent).toLocaleString(currentIntlLocale(), {
                                    minimumFractionDigits: 0,
                                    maximumFractionDigits: 2,
                                })} {lease.currency}
                            </p>
                            <p>
                                {t('renter.overview.dueDay')}: {lease.dueDay ?? '—'}
                            </p>
                            <p>
                                {t('renter.overview.period')}: {formatDateShort(lease.startDate)}
                                {' – '}
                                {lease.endDate ? formatDateShort(lease.endDate) : t('renter.overview.ongoing')}
                            </p>
                            <p>
                                {t('renter.overview.status')}: {leaseStatusLabel(lease.status)}
                            </p>
                        </section>
                    ))}
                </div>
            )}
        </main>
    );
}
