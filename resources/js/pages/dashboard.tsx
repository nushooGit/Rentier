import { Head, Link, router, usePage } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';
import {
    AlertTriangle,
    Building2,
    CircleDollarSign,
    ReceiptText,
    TrendingUp,
    WalletCards,
} from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import PendingInvitationsModal from '@/components/pending-invitations-modal';
import { Badge } from '@/components/ui/badge';
import { formatDateLong } from '@/lib/date';
import { formatMoney } from '@/lib/money';
import { expenseStatusLabel } from '@/pages/expenses/labels';
import { leaseStatusLabel } from '@/pages/leases/labels';
import { paymentStatusLabel } from '@/pages/payments/labels';
import { dashboard } from '@/routes';
import { show as showExpense } from '@/routes/expenses';
import { show as showLease } from '@/routes/leases';
import { show as showPayment } from '@/routes/payments';
import { show as showProperty } from '@/routes/properties';
import type {
    DashboardInvitation,
    DashboardLeaseFinancialRow,
    DashboardPaymentMethodBreakdown,
    DashboardPropertyWithoutActiveLease,
    DashboardRecentExpense,
    DashboardRecentLease,
    DashboardRecentPayment,
    DashboardSummary,
} from '@/types';

type Props = {
    pendingInvitations?: DashboardInvitation[];
    summary: DashboardSummary;
    propertyStatusSummary: {
        active: number;
        available: number;
    };
    overdueLeases: DashboardLeaseFinancialRow[];
    upcomingPayments: DashboardLeaseFinancialRow[];
    advanceLeases: DashboardLeaseFinancialRow[];
    propertiesWithoutActiveLease: DashboardPropertyWithoutActiveLease[];
    recentLeases: DashboardRecentLease[];
    recentPayments: DashboardRecentPayment[];
    recentExpenses: DashboardRecentExpense[];
    rentPaymentMethodBreakdown: DashboardPaymentMethodBreakdown[];
};

function SummaryCard({
    label,
    value,
    description,
    testId,
    icon: Icon,
    tone = 'default',
}: {
    label: string;
    value: string | number;
    description?: string;
    testId?: string;
    icon?: LucideIcon;
    tone?: 'default' | 'success' | 'danger' | 'warning' | 'info';
}) {
    const toneClasses = {
        default: 'border-border/70 bg-card text-foreground',
        success:
            'border-emerald-200/80 bg-emerald-50/80 text-emerald-950 dark:border-emerald-400/20 dark:bg-emerald-400/10 dark:text-emerald-100',
        danger:
            'border-rose-200/80 bg-rose-50/80 text-rose-950 dark:border-rose-400/20 dark:bg-rose-400/10 dark:text-rose-100',
        warning:
            'border-amber-200/80 bg-amber-50/80 text-amber-950 dark:border-amber-400/20 dark:bg-amber-400/10 dark:text-amber-100',
        info: 'border-sky-200/80 bg-sky-50/80 text-sky-950 dark:border-sky-400/20 dark:bg-sky-400/10 dark:text-sky-100',
    };

    return (
        <section
            className={`rounded-2xl border p-4 shadow-sm ${toneClasses[tone]} sm:p-5`}
            data-test={testId}
            data-testid={testId}
        >
            <div className="flex items-start justify-between gap-3">
                <p className="text-xs font-semibold uppercase tracking-[0.12em] opacity-60">
                    {label}
                </p>
                {Icon ? (
                    <span className="flex size-9 shrink-0 items-center justify-center rounded-xl bg-current/5">
                        <Icon className="size-4" aria-hidden="true" />
                    </span>
                ) : null}
            </div>
            <p className="mt-3 text-2xl font-semibold tracking-tight sm:text-[1.7rem]">
                {value}
            </p>
            {description ? (
                <p className="mt-1.5 text-xs leading-5 opacity-60">
                    {description}
                </p>
            ) : null}
        </section>
    );
}

function EmptyLine({ children }: { children: string }) {
    return <p className="text-sm text-muted-foreground">{children}</p>;
}

function PaymentMethodBreakdownCard({
    methods,
    currency,
}: {
    methods: DashboardPaymentMethodBreakdown[];
    currency: string;
}) {
    const total = methods.reduce(
        (sum, method) => sum + Number(method.amount),
        0,
    );

    return (
        <section className="rounded-2xl border border-border/70 bg-card p-4 shadow-sm sm:p-5">
            <p className="text-xs font-semibold uppercase tracking-[0.12em] text-muted-foreground">
                Încasări pe metode
            </p>
            <div className="mt-2 space-y-1.5 text-sm">
                {methods.length > 0 ? (
                    methods.map((method) => (
                        <div
                            key={method.method ?? 'unset'}
                            className="flex items-center justify-between gap-3"
                        >
                            <span className="text-muted-foreground">
                                {method.label}
                            </span>
                            <span className="font-medium">
                                {formatMoney(method.amount, method.currency)}
                            </span>
                        </div>
                    ))
                ) : (
                    <span className="text-muted-foreground">
                        Nu există chirii încasate luna asta.
                    </span>
                )}
            </div>
            <p className="mt-2 text-xs text-muted-foreground">
                Total: {formatMoney(String(total), currency)}
            </p>
        </section>
    );
}

function FinancialLeaseLine({
    lease,
    href,
    tone = 'neutral',
}: {
    lease: DashboardLeaseFinancialRow;
    href: ReturnType<typeof showLease>;
    tone?: 'neutral' | 'danger';
}) {
    return (
        <Link
            href={href}
            className="block rounded-xl border border-transparent bg-muted/40 p-3 text-sm transition-colors hover:border-border hover:bg-muted/70 focus:outline-none focus-visible:ring-2 focus-visible:ring-ring"
            data-test="dashboard-lease-link"
            aria-label={`Vezi contractul pentru ${lease.property_name}`}
        >
            <div className="flex items-start justify-between gap-3">
                <div className="min-w-0">
                    <p className="truncate font-medium">
                        {lease.property_name}
                    </p>
                    <p className="text-muted-foreground">
                        {lease.renter_name}
                        {tone === 'neutral' ? (
                            <> · scadentă {formatDateLong(lease.due_date)}</>
                        ) : null}
                    </p>
                </div>
                <div className="flex flex-wrap justify-end gap-1">
                    {lease.status_key === 'partial_overdue' ? (
                        <Badge
                            variant="outline"
                            className="border-amber-200 bg-amber-50 text-amber-700"
                        >
                            Plătită parțial
                        </Badge>
                    ) : null}
                    <Badge
                        variant="outline"
                        className={
                            tone === 'danger'
                                ? 'border-red-200 bg-red-50 text-red-700'
                                : 'border-slate-200 bg-slate-50 text-slate-700'
                        }
                    >
                        {lease.status_label}
                    </Badge>
                    {tone === 'danger' && lease.overdue_month_count > 1 ? (
                        <Badge
                            variant="outline"
                            className="border-red-200 bg-red-50 text-red-700"
                        >
                            {lease.overdue_month_count} luni restante
                        </Badge>
                    ) : null}
                </div>
            </div>
            <div className="mt-2 grid gap-1 text-xs text-muted-foreground sm:grid-cols-2">
                {tone === 'danger' ? (
                    <>
                        <span
                            className="font-medium text-red-700 sm:col-span-2"
                            data-test="dashboard-rent-arrears"
                        >
                            Restanță totală:{' '}
                            {formatMoney(lease.arrears_amount, lease.currency)}
                        </span>
                        <span className="sm:col-span-2">
                            {lease.overdue_month_count === 1
                                ? '1 lună restantă'
                                : `${lease.overdue_month_count} luni restante`}
                        </span>
                        {lease.oldest_overdue_due_date ? (
                            <span className="sm:col-span-2">
                                Cea mai veche scadență:{' '}
                                {formatDateLong(lease.oldest_overdue_due_date)}
                            </span>
                        ) : null}
                        {lease.overdue_months.map((month) => (
                            <span
                                key={month.period_key}
                                className="sm:col-span-2"
                            >
                                {month.period_label} — Rest{' '}
                                {formatMoney(
                                    month.remaining_amount,
                                    lease.currency,
                                )}
                            </span>
                        ))}
                    </>
                ) : (
                    <>
                        <span>
                            Chirie:{' '}
                            {formatMoney(lease.expected_amount, lease.currency)}
                        </span>
                        <span>
                            Încasat:{' '}
                            {formatMoney(
                                lease.collected_amount,
                                lease.currency,
                            )}
                        </span>
                        <span>
                            Scăzut din chirie:{' '}
                            {formatMoney(
                                lease.rent_deduction_amount,
                                lease.currency,
                            )}
                        </span>
                        <span>
                            Rest:{' '}
                            <strong className="font-medium text-foreground">
                                {formatMoney(
                                    lease.remaining_amount,
                                    lease.currency,
                                )}
                            </strong>
                        </span>
                    </>
                )}
                {((lease.advance_notices ?? []).length > 0
                    ? lease.advance_notices
                    : lease.advance_notice
                      ? [lease.advance_notice]
                      : []
                ).map((notice) => (
                    <span
                        key={`${notice.key}-${notice.period_key}`}
                        className="font-medium text-emerald-700 sm:col-span-2"
                    >
                        {notice.label}
                    </span>
                ))}
            </div>
        </Link>
    );
}

export default function Dashboard({
    pendingInvitations = [],
    summary,
    overdueLeases,
    upcomingPayments,
    advanceLeases,
    propertiesWithoutActiveLease,
    recentLeases,
    recentPayments,
    recentExpenses,
    rentPaymentMethodBreakdown,
}: Props) {
    const hasRefreshed = useRef(false);
    const { currentTeam } = usePage().props;
    const currentTeamSlug = currentTeam?.slug ?? '';
    const [showInvitations, setShowInvitations] = useState(
        pendingInvitations.length > 0,
    );

    useEffect(() => {
        if (hasRefreshed.current) {
            return;
        }

        hasRefreshed.current = true;
        router.reload();
    }, []);

    return (
        <>
            <Head title="Dashboard" />
            <PendingInvitationsModal
                invitations={pendingInvitations}
                open={pendingInvitations.length > 0 && showInvitations}
                onOpenChange={setShowInvitations}
            />

            <div className="mx-auto flex w-full max-w-[1480px] flex-col gap-5 p-3 sm:p-5 lg:p-6">
                <section className="overflow-hidden rounded-3xl border border-slate-200/80 bg-gradient-to-br from-[#0b1a2b] via-[#0c2031] to-[#0d2a32] px-5 py-6 text-white shadow-sm sm:px-7 sm:py-7">
                    <div className="flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <p className="text-xs font-semibold uppercase tracking-[0.18em] text-emerald-300">
                                Panou de control
                            </p>
                            <h1 className="mt-2 text-3xl font-semibold tracking-[-0.03em] sm:text-4xl">
                                Situația lunii, dintr-o privire.
                            </h1>
                            <p className="mt-2 max-w-2xl text-sm leading-6 text-slate-300">
                                {currentTeam?.name
                                    ? `Workspace: ${currentTeam.name}`
                                    : 'Rezumatul workspace-ului curent'}
                            </p>
                        </div>
                        <div className="inline-flex w-fit items-center gap-2 rounded-full border border-white/10 bg-white/5 px-3 py-2 text-xs font-medium text-slate-300">
                            <Building2 className="size-4 text-emerald-300" aria-hidden="true" />
                            {summary.property_count} proprietăți · {summary.active_lease_count} contracte active
                        </div>
                    </div>
                </section>

                <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                    <SummaryCard
                        label="Încasat luna asta"
                        value={formatMoney(
                            summary.current_month_payments,
                            summary.currency,
                        )}
                        description="Chirii încasate, fără garanții"
                        testId="dashboard-rent-collected"
                        icon={WalletCards}
                        tone="success"
                    />
                    <SummaryCard
                        label="Rest de încasat"
                        value={formatMoney(
                            summary.remaining_rent,
                            summary.currency,
                        )}
                        description={`${summary.overdue_month_count} luni restante în evidență`}
                        icon={AlertTriangle}
                        tone={summary.overdue_count > 0 ? 'danger' : 'default'}
                    />
                    <SummaryCard
                        label="Cheltuieli luna asta"
                        value={formatMoney(
                            summary.current_month_expenses,
                            summary.currency,
                        )}
                        description="Suportate economic de proprietar"
                        icon={ReceiptText}
                        tone="warning"
                    />
                    <SummaryCard
                        label="De recuperat"
                        value={formatMoney(
                            summary.recoverable_expenses,
                            summary.currency,
                        )}
                        description="Sume de recuperat de la chiriași"
                        icon={CircleDollarSign}
                        tone="info"
                    />
                </div>

                <section className="rounded-2xl border border-border/70 bg-card p-4 shadow-sm sm:p-5">
                    <div className="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                        <div>
                            <p className="text-xs font-semibold uppercase tracking-[0.12em] text-muted-foreground">
                                Necesită atenția ta
                            </p>
                            <h2 className="mt-1 text-lg font-semibold tracking-tight">
                                Ce merită verificat acum
                            </h2>
                        </div>
                        <div className="grid gap-2 sm:grid-cols-3 lg:min-w-[620px]">
                            <div className="rounded-xl bg-rose-50 px-3 py-2.5 dark:bg-rose-400/10">
                                <p className="text-xs text-rose-700 dark:text-rose-300">
                                    Chirii întârziate
                                </p>
                                <p className="mt-1 text-lg font-semibold text-rose-950 dark:text-rose-100">
                                    {summary.overdue_count}
                                </p>
                            </div>
                            <div className="rounded-xl bg-amber-50 px-3 py-2.5 dark:bg-amber-400/10">
                                <p className="text-xs text-amber-700 dark:text-amber-300">
                                    Scadențe următoarele 7 zile
                                </p>
                                <p className="mt-1 text-lg font-semibold text-amber-950 dark:text-amber-100">
                                    {upcomingPayments.length}
                                </p>
                            </div>
                            <div className="rounded-xl bg-slate-100 px-3 py-2.5 dark:bg-white/5">
                                <p className="text-xs text-slate-600 dark:text-slate-300">
                                    Fără contract activ
                                </p>
                                <p className="mt-1 text-lg font-semibold">
                                    {propertiesWithoutActiveLease.length}
                                </p>
                            </div>
                        </div>
                    </div>
                </section>

                <section>
                    <div className="mb-3 flex items-center gap-2">
                        <TrendingUp className="size-4 text-emerald-600 dark:text-emerald-300" aria-hidden="true" />
                        <h2 className="text-sm font-semibold uppercase tracking-[0.12em] text-muted-foreground">
                            Detalii financiare
                        </h2>
                    </div>
                    <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                        <SummaryCard
                            label="Chirie estimată"
                            value={formatMoney(
                                summary.estimated_monthly_rent,
                                summary.currency,
                            )}
                            description={`${summary.active_lease_count} contracte active`}
                        />
                        <SummaryCard
                            label="Profit estimat"
                            value={formatMoney(
                                summary.current_month_profit,
                                summary.currency,
                            )}
                            description="Chirie estimată minus cheltuieli suportate"
                        />
                        <SummaryCard
                            label="Rezultat operațional"
                            value={formatMoney(
                                summary.operational_cash_result,
                                summary.currency,
                            )}
                            description="Încasări minus plăți efective"
                        />
                        <SummaryCard
                            label="Total de încasat"
                            value={formatMoney(
                                summary.total_receivable,
                                summary.currency,
                            )}
                            description="Chirie + garanții + recuperări"
                        />
                        <SummaryCard
                            label="Garanții de încasat"
                            value={formatMoney(
                                summary.expected_guarantees,
                                summary.currency,
                            )}
                        />
                        <SummaryCard
                            label="Garanții încasate"
                            value={formatMoney(
                                summary.collected_guarantees,
                                summary.currency,
                            )}
                        />
                        <SummaryCard
                            label="Garanții restante"
                            value={formatMoney(
                                summary.remaining_guarantees,
                                summary.currency,
                            )}
                        />
                        <SummaryCard
                            label="Grad de ocupare"
                            value={summary.occupancy_label}
                            description={`${summary.occupancy_rate}% din ${summary.property_count} proprietăți`}
                        />
                        <SummaryCard
                            label="Scăzut din chirie"
                            value={formatMoney(
                                summary.current_month_rent_deductions,
                                summary.currency,
                            )}
                        />
                        <SummaryCard
                            label="De rambursat chiriașului"
                            value={formatMoney(
                                summary.tenant_reimbursement_expenses,
                                summary.currency,
                            )}
                        />
                        <SummaryCard
                            label="De scăzut din utilități"
                            value={formatMoney(
                                summary.utility_deduction_expenses,
                                summary.currency,
                            )}
                        />
                        <PaymentMethodBreakdownCard
                            methods={rentPaymentMethodBreakdown}
                            currency={summary.currency}
                        />
                    </div>
                </section>

                {Number(summary.unsettled_tenant_paid_owner_expenses) > 0 ? (
                    <section className="rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-900 sm:p-3.5">
                        Exista cheltuieli platite de chirias, suportate de
                        proprietar, fara decontare:{' '}
                        <strong>
                            {formatMoney(
                                summary.unsettled_tenant_paid_owner_expenses,
                                summary.currency,
                            )}
                        </strong>
                        . Editeaza cheltuielile si alege scadere din chirie,
                        scadere din utilitati sau rambursare.
                    </section>
                ) : null}

                <div className="grid gap-3 lg:grid-cols-2 xl:grid-cols-4">
                    <section className="rounded-2xl border border-border/70 bg-card p-4 shadow-sm sm:p-5">
                        <h2 className="text-base font-medium">
                            Chirii întârziate
                        </h2>
                        <div className="mt-2.5 space-y-2">
                            {overdueLeases.length > 0 ? (
                                overdueLeases.map((lease) => (
                                    <FinancialLeaseLine
                                        key={lease.lease_id}
                                        lease={lease}
                                        href={showLease([
                                            currentTeamSlug,
                                            lease.lease_id,
                                        ])}
                                        tone="danger"
                                    />
                                ))
                            ) : (
                                <EmptyLine>
                                    Nu există chirii întârziate luna asta.
                                </EmptyLine>
                            )}
                        </div>
                    </section>

                    <section className="rounded-2xl border border-border/70 bg-card p-4 shadow-sm sm:p-5">
                        <h2 className="text-base font-medium">
                            Plăți care urmează
                        </h2>
                        <div className="mt-2.5 space-y-2">
                            {upcomingPayments.length > 0 ? (
                                upcomingPayments.map((lease) => (
                                    <FinancialLeaseLine
                                        key={lease.lease_id}
                                        lease={lease}
                                        href={showLease([
                                            currentTeamSlug,
                                            lease.lease_id,
                                        ])}
                                    />
                                ))
                            ) : (
                                <EmptyLine>
                                    Nu sunt plăți scadente în următoarele 7
                                    zile.
                                </EmptyLine>
                            )}
                        </div>
                    </section>

                    <section className="rounded-2xl border border-border/70 bg-card p-4 shadow-sm sm:p-5">
                        <h2 className="text-base font-medium">
                            Chirii plătite în avans
                        </h2>
                        <div className="mt-2.5 space-y-2">
                            {advanceLeases.length > 0 ? (
                                advanceLeases.map((lease) => (
                                    <FinancialLeaseLine
                                        key={lease.lease_id}
                                        lease={lease}
                                        href={showLease([
                                            currentTeamSlug,
                                            lease.lease_id,
                                        ])}
                                    />
                                ))
                            ) : (
                                <EmptyLine>
                                    Nu există chirii plătite în avans.
                                </EmptyLine>
                            )}
                        </div>
                    </section>

                    <section className="rounded-2xl border border-border/70 bg-card p-4 shadow-sm sm:p-5">
                        <h2 className="text-base font-medium">
                            Proprietăți fără contract activ
                        </h2>
                        <div className="mt-2.5 space-y-2">
                            {propertiesWithoutActiveLease.length > 0 ? (
                                propertiesWithoutActiveLease.map((property) => (
                                    <Link
                                        key={property.id}
                                        href={showProperty([
                                            currentTeamSlug,
                                            property.id,
                                        ])}
                                        className="block rounded-xl border border-transparent bg-muted/40 p-3 text-sm transition-colors hover:border-border hover:bg-muted/70 focus:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                                        data-test="dashboard-property-link"
                                        aria-label={`Vezi proprietatea ${property.name}`}
                                    >
                                        <p className="truncate font-medium">
                                            {property.name}
                                        </p>
                                        <p className="text-muted-foreground">
                                            {property.city} ·{' '}
                                            {property.address_line}
                                        </p>
                                        <p className="mt-1 text-xs text-muted-foreground">
                                            Chirie listată:{' '}
                                            {formatMoney(
                                                property.monthly_rent_amount,
                                                property.currency,
                                            )}
                                        </p>
                                    </Link>
                                ))
                            ) : (
                                <EmptyLine>
                                    Toate proprietățile au contract activ.
                                </EmptyLine>
                            )}
                        </div>
                    </section>
                </div>

                <div className="grid gap-3 lg:grid-cols-3">
                    <section className="rounded-2xl border border-border/70 bg-card p-4 shadow-sm sm:p-5">
                        <h2 className="text-base font-medium">
                            Contracte recente
                        </h2>
                        <div className="mt-2.5 space-y-2.5">
                            {recentLeases.length > 0 ? (
                                recentLeases.map((lease) => (
                                    <Link
                                        key={lease.id}
                                        href={showLease([
                                            currentTeamSlug,
                                            lease.id,
                                        ])}
                                        className="flex items-start justify-between gap-3 rounded-xl p-3 text-sm transition-colors hover:bg-muted/70 focus:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                                        data-test="dashboard-recent-lease-link"
                                        aria-label={`Vezi contractul pentru ${lease.renter_name}`}
                                    >
                                        <div className="min-w-0">
                                            <p className="truncate font-medium">
                                                {lease.renter_name}
                                            </p>
                                            <p className="text-muted-foreground">
                                                {lease.property_name} ·{' '}
                                                {formatMoney(
                                                    lease.monthly_rent_amount,
                                                    lease.currency,
                                                )}
                                            </p>
                                            <p className="text-xs text-muted-foreground">
                                                Început:{' '}
                                                {formatDateLong(
                                                    lease.start_date,
                                                )}
                                            </p>
                                        </div>
                                        <Badge variant="secondary">
                                            {leaseStatusLabel(lease.status)}
                                        </Badge>
                                    </Link>
                                ))
                            ) : (
                                <EmptyLine>
                                    Nu există contracte recente.
                                </EmptyLine>
                            )}
                        </div>
                    </section>

                    <section className="rounded-2xl border border-border/70 bg-card p-4 shadow-sm sm:p-5">
                        <h2 className="text-base font-medium">Plăți recente</h2>
                        <div className="mt-2.5 space-y-2.5">
                            {recentPayments.length > 0 ? (
                                recentPayments.map((payment) => (
                                    <Link
                                        key={payment.id}
                                        href={showPayment([
                                            currentTeamSlug,
                                            payment.id,
                                        ])}
                                        className="flex items-start justify-between gap-3 rounded-xl p-3 text-sm transition-colors hover:bg-muted/70 focus:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                                        data-test="dashboard-recent-payment-link"
                                        aria-label={`Vezi plata pentru ${payment.renter_name}`}
                                    >
                                        <div className="min-w-0">
                                            <p className="truncate font-medium">
                                                {payment.renter_name}
                                            </p>
                                            <p className="text-muted-foreground">
                                                {payment.property_name} ·{' '}
                                                {formatMoney(
                                                    payment.amount,
                                                    payment.currency,
                                                )}
                                            </p>
                                            <p className="text-xs text-muted-foreground">
                                                Încasată:{' '}
                                                {formatDateLong(
                                                    payment.payment_date,
                                                )}
                                            </p>
                                        </div>
                                        <Badge variant="secondary">
                                            {paymentStatusLabel(payment.status)}
                                        </Badge>
                                    </Link>
                                ))
                            ) : (
                                <EmptyLine>Nu există plăți recente.</EmptyLine>
                            )}
                        </div>
                    </section>

                    <section className="rounded-2xl border border-border/70 bg-card p-4 shadow-sm sm:p-5">
                        <h2 className="text-base font-medium">
                            Cheltuieli recente
                        </h2>
                        <div className="mt-2.5 space-y-2.5">
                            {recentExpenses.length > 0 ? (
                                recentExpenses.map((expense) => (
                                    <Link
                                        key={expense.id}
                                        href={showExpense([
                                            currentTeamSlug,
                                            expense.id,
                                        ])}
                                        className="flex items-start justify-between gap-3 rounded-xl p-3 text-sm transition-colors hover:bg-muted/70 focus:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                                        data-test="dashboard-recent-expense-link"
                                        aria-label={`Vezi cheltuiala ${expense.title}`}
                                    >
                                        <div className="min-w-0">
                                            <p className="truncate font-medium">
                                                {expense.title}
                                            </p>
                                            <p className="text-muted-foreground">
                                                {expense.property_name} ·{' '}
                                                {formatMoney(
                                                    expense.amount,
                                                    expense.currency,
                                                )}
                                            </p>
                                            <p className="text-xs text-muted-foreground">
                                                Data:{' '}
                                                {formatDateLong(
                                                    expense.expense_date,
                                                )}
                                            </p>
                                        </div>
                                        <Badge variant="secondary">
                                            {expenseStatusLabel(expense.status)}
                                        </Badge>
                                    </Link>
                                ))
                            ) : (
                                <EmptyLine>
                                    Nu există cheltuieli recente.
                                </EmptyLine>
                            )}
                        </div>
                    </section>
                </div>
            </div>
        </>
    );
}

Dashboard.layout = (props: { currentTeam?: { slug: string } | null }) => ({
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: props.currentTeam ? dashboard(props.currentTeam.slug) : '/',
        },
    ],
});
