import { Head, Link, router, usePage } from '@inertiajs/react';
import { Eye, Pencil, Plus, Trash2, WalletCards } from 'lucide-react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { formatDateLong, formatMonthYear } from '@/lib/date';
import { translateKey, useI18n } from '@/lib/i18n';
import { currentAppLocale } from '@/lib/locale';
import { formatMoney } from '@/lib/money';
import {
    paymentMethodLabel,
    paymentSummaryLabel,
    paymentTypeLabel,
} from '@/pages/payments/labels';
import { create, destroy, edit, index, show } from '@/routes/payments';
import type { RentPayment } from '@/types';

type Props = {
    payments: RentPayment[];
};

const monthNamesRo = [
    'Ianuarie',
    'Februarie',
    'Martie',
    'Aprilie',
    'Mai',
    'Iunie',
    'Iulie',
    'August',
    'Septembrie',
    'Octombrie',
    'Noiembrie',
    'Decembrie',
];

const monthNamesEn = [
    'January',
    'February',
    'March',
    'April',
    'May',
    'June',
    'July',
    'August',
    'September',
    'October',
    'November',
    'December',
];

function formatRentPeriod(month: number | null, year: number | null) {
    if (month === null || year === null) {
        return translateKey('payments.index.noPeriod');
    }

    const monthNames = currentAppLocale() === 'en' ? monthNamesEn : monthNamesRo;

    return `${monthNames[month - 1] ?? month} ${year}`;
}

function paymentBadgeLabel(payment: RentPayment) {
    return paymentSummaryLabel(payment.payment_type, payment.status_summary);
}

function paymentContext(payment: RentPayment) {
    if (payment.payment_type === 'guarantee' && payment.guarantee_summary) {
        const collectedAmount = Number(
            payment.guarantee_summary.collected_amount,
        );
        const expectedAmount = Number(
            payment.guarantee_summary.expected_amount,
        );
        const prefix =
            expectedAmount > 0 && collectedAmount > expectedAmount
                ? translateKey('payments.index.overDeposit')
                : paymentTypeLabel('guarantee');

        return `${prefix}: ${formatMoney(
            payment.guarantee_summary.collected_amount,
            payment.currency,
        )} / ${formatMoney(
            payment.guarantee_summary.expected_amount,
            payment.currency,
        )}`;
    }

    return formatRentPeriod(payment.period_month, payment.period_year);
}

function paymentMetaLine(payment: RentPayment) {
    return `${formatDateLong(payment.payment_date)} - ${paymentMethodLabel(
        payment.method,
    )} - ${paymentContext(payment)}`;
}

function AllocationSummary({ payment }: { payment: RentPayment }) {
    const { t } = useI18n();

    if (
        payment.payment_type === 'guarantee' ||
        !payment.allocation_summary ||
        (payment.allocation_summary.breakdown.length === 0 &&
            Number(payment.allocation_summary.unallocated_amount) <= 0)
    ) {
        return null;
    }

    return (
        <div className="mt-1 grid gap-1 text-xs text-muted-foreground">
            <span className="font-medium text-foreground">{t('payments.section.allocation')}:</span>
            {payment.allocation_summary.breakdown.map((allocation) => (
                <span key={allocation.period_key}>
                    {formatMonthYear(allocation.period_date)} -{' '}
                    {formatMoney(allocation.amount, payment.currency)}
                </span>
            ))}
            {Number(payment.allocation_summary.unallocated_amount) > 0 ? (
                <span className="font-medium text-amber-700">
                    {t('payments.field.unallocated')}:{' '}
                    {formatMoney(
                        payment.allocation_summary.unallocated_amount,
                        payment.currency,
                    )}
                </span>
            ) : null}
        </div>
    );
}

export default function PaymentsIndex({ payments }: Props) {
    const { currentTeam } = usePage().props;
    const { t } = useI18n();
    const currentTeamSlug = currentTeam?.slug ?? '';

    const deletePayment = (payment: RentPayment) => {
        if (!window.confirm(t('payments.index.deleteConfirm'))) {
            return;
        }

        router.delete(destroy([currentTeamSlug, payment.id]).url);
    };

    return (
        <>
            <Head title={t('nav.payments')} />

            <div className="mx-auto flex w-full max-w-7xl flex-col space-y-3.5 p-3 sm:p-4">
                <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <Heading
                        variant="small"
                        title={t('nav.payments')}
                        description={t('payments.index.description')}
                    />
                    <Button asChild data-test="payment-create-link">
                        <Link href={create(currentTeamSlug)}>
                            <Plus /> {t('payments.index.new')}
                        </Link>
                    </Button>
                </div>

                {payments.length > 0 ? (
                    <div className="grid gap-2.5 md:grid-cols-2 xl:grid-cols-3">
                        {payments.map((payment) => (
                            <article
                                key={payment.id}
                                className="flex flex-col rounded-2xl border border-border/70 bg-card/90 shadow-sm transition-all focus-within:border-primary/35 hover:-translate-y-0.5 hover:border-primary/30 hover:shadow-md"
                                data-test="payment-card"
                            >
                                <Link
                                    href={show([currentTeamSlug, payment.id])}
                                    className="flex flex-1 cursor-pointer flex-col gap-3 rounded-2xl p-4 focus:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                                    data-test="payment-card-link"
                                    aria-label={t('payments.index.view', { name: payment.renter.name })}
                                >
                                    <div className="flex items-start justify-between gap-3">
                                        <div className="min-w-0">
                                            <h2 className="truncate text-base font-medium">
                                                {payment.renter.name}
                                            </h2>
                                            <p className="mt-1 text-sm text-muted-foreground">
                                                {payment.property.name} - Tip:{' '}
                                                {paymentTypeLabel(
                                                    payment.payment_type,
                                                )}
                                            </p>
                                        </div>
                                        <Badge variant="secondary">
                                            {paymentBadgeLabel(payment)}
                                        </Badge>
                                    </div>
                                    <div className="grid gap-0.5 text-sm">
                                        <span className="font-medium">
                                            {formatMoney(
                                                payment.amount,
                                                payment.currency,
                                            )}
                                        </span>
                                        <span className="text-muted-foreground">
                                            {paymentMetaLine(payment)}
                                        </span>
                                        <AllocationSummary payment={payment} />
                                    </div>
                                </Link>
                                <div className="mt-auto flex justify-end gap-2 px-3 pb-3">
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        asChild
                                        data-test="payment-view-link"
                                    >
                                        <Link
                                            href={show([
                                                currentTeamSlug,
                                                payment.id,
                                            ])}
                                        >
                                            <Eye className="h-4 w-4" />
                                        </Link>
                                    </Button>
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        asChild
                                        data-test="payment-edit-link"
                                    >
                                        <Link
                                            href={edit([
                                                currentTeamSlug,
                                                payment.id,
                                            ])}
                                        >
                                            <Pencil className="h-4 w-4" />
                                        </Link>
                                    </Button>
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        type="button"
                                        onClick={() => deletePayment(payment)}
                                        aria-label={t('payments.index.delete')}
                                        data-test="payment-delete-button"
                                    >
                                        <Trash2 className="h-4 w-4" />
                                    </Button>
                                </div>
                            </article>
                        ))}
                    </div>
                ) : (
                    <div className="rounded-lg border border-dashed p-5 text-center sm:p-6">
                        <WalletCards className="mx-auto h-8 w-8 text-muted-foreground" />
                        <h2 className="mt-3 text-base font-medium">
                            {t('payments.index.emptyTitle')}
                        </h2>
                        <p className="mt-1 text-sm text-muted-foreground">
                            {t('payments.index.emptyDescription')}
                        </p>
                        <Button className="mt-4" asChild>
                            <Link href={create(currentTeamSlug)}>
                                <Plus /> {t('payments.index.new')}
                            </Link>
                        </Button>
                    </div>
                )}
            </div>
        </>
    );
}

PaymentsIndex.layout = (props: { currentTeam?: { slug: string } | null }) => ({
    breadcrumbs: [
        {
            title: translateKey('nav.payments'),
            href: props.currentTeam ? index(props.currentTeam.slug) : '/',
        },
    ],
});
