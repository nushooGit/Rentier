import { Head } from '@inertiajs/react';
import { useState } from 'react';
import Heading from '@/components/heading';
import PendingInvitationsModal from '@/components/pending-invitations-modal';
import { Badge } from '@/components/ui/badge';
import { formatDateShort } from '@/lib/date';
import { formatMoney } from '@/lib/money';
import { expenseStatusLabel } from '@/pages/expenses/labels';
import { leaseStatusLabel } from '@/pages/leases/labels';
import { paymentStatusLabel } from '@/pages/payments/labels';
import { dashboard } from '@/routes';
import type {
    DashboardInvitation,
    DashboardLeaseFinancialRow,
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
    propertiesWithoutActiveLease: DashboardPropertyWithoutActiveLease[];
    recentLeases: DashboardRecentLease[];
    recentPayments: DashboardRecentPayment[];
    recentExpenses: DashboardRecentExpense[];
};

function SummaryCard({
    label,
    value,
    description,
}: {
    label: string;
    value: string | number;
    description?: string;
}) {
    return (
        <section className="rounded-lg border p-3 sm:p-3.5">
            <p className="text-xs text-muted-foreground">{label}</p>
            <p className="mt-1 text-lg font-semibold">{value}</p>
            {description ? (
                <p className="mt-1 text-xs text-muted-foreground">
                    {description}
                </p>
            ) : null}
        </section>
    );
}

function EmptyLine({ children }: { children: string }) {
    return <p className="text-sm text-muted-foreground">{children}</p>;
}

function FinancialLeaseLine({
    lease,
    tone = 'neutral',
}: {
    lease: DashboardLeaseFinancialRow;
    tone?: 'neutral' | 'danger';
}) {
    return (
        <div className="rounded-md bg-muted/40 p-2 text-sm">
            <div className="flex items-start justify-between gap-3">
                <div className="min-w-0">
                    <p className="truncate font-medium">
                        {lease.property_name}
                    </p>
                    <p className="text-muted-foreground">
                        {lease.renter_name} · scadentă{' '}
                        {formatDateShort(lease.due_date)}
                    </p>
                </div>
                <Badge
                    variant="outline"
                    className={
                        tone === 'danger'
                            ? 'border-red-200 bg-red-50 text-red-700'
                            : 'border-slate-200 bg-slate-50 text-slate-700'
                    }
                >
                    {lease.days !== null && tone === 'danger'
                        ? `${lease.days} ${
                              lease.days === 1 ? 'zi' : 'zile'
                          } întârziere`
                        : lease.status_label}
                </Badge>
            </div>
            <div className="mt-2 grid gap-1 text-xs text-muted-foreground sm:grid-cols-2">
                <span>
                    Chirie: {formatMoney(lease.expected_amount, lease.currency)}
                </span>
                <span>
                    Rest:{' '}
                    <strong className="font-medium text-foreground">
                        {formatMoney(lease.remaining_amount, lease.currency)}
                    </strong>
                </span>
            </div>
        </div>
    );
}

export default function Dashboard({
    pendingInvitations = [],
    summary,
    overdueLeases,
    upcomingPayments,
    propertiesWithoutActiveLease,
    recentLeases,
    recentPayments,
    recentExpenses,
}: Props) {
    const [showInvitations, setShowInvitations] = useState(
        pendingInvitations.length > 0,
    );

    return (
        <>
            <Head title="Dashboard" />
            <PendingInvitationsModal
                invitations={pendingInvitations}
                open={pendingInvitations.length > 0 && showInvitations}
                onOpenChange={setShowInvitations}
            />

            <div className="mx-auto flex w-full max-w-7xl flex-col space-y-3.5 p-3 sm:p-4">
                <Heading
                    variant="small"
                    title="Dashboard"
                    description="Rezumat financiar pentru workspace-ul curent"
                />

                <div className="grid gap-2.5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5">
                    <SummaryCard
                        label="Chirie estimată luna asta"
                        value={formatMoney(
                            summary.estimated_monthly_rent,
                            summary.currency,
                        )}
                        description={`${summary.active_lease_count} contracte active`}
                    />
                    <SummaryCard
                        label="Încasat luna asta"
                        value={formatMoney(
                            summary.current_month_payments,
                            summary.currency,
                        )}
                    />
                    <SummaryCard
                        label="Rest de încasat"
                        value={formatMoney(
                            summary.remaining_rent,
                            summary.currency,
                        )}
                    />
                    <SummaryCard
                        label="Chirii întârziate"
                        value={summary.overdue_count}
                    />
                    <SummaryCard
                        label="Grad de ocupare"
                        value={summary.occupancy_label}
                        description={`${summary.occupancy_rate}% din ${summary.property_count} proprietăți`}
                    />
                </div>

                <div className="grid gap-3 lg:grid-cols-3">
                    <section className="rounded-lg border p-3 sm:p-3.5">
                        <h2 className="text-base font-medium">
                            Chirii întârziate
                        </h2>
                        <div className="mt-2.5 space-y-2">
                            {overdueLeases.length > 0 ? (
                                overdueLeases.map((lease) => (
                                    <FinancialLeaseLine
                                        key={lease.lease_id}
                                        lease={lease}
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

                    <section className="rounded-lg border p-3 sm:p-3.5">
                        <h2 className="text-base font-medium">
                            Plăți care urmează
                        </h2>
                        <div className="mt-2.5 space-y-2">
                            {upcomingPayments.length > 0 ? (
                                upcomingPayments.map((lease) => (
                                    <FinancialLeaseLine
                                        key={lease.lease_id}
                                        lease={lease}
                                    />
                                ))
                            ) : (
                                <EmptyLine>
                                    Nu sunt plăți scadente în următoarele 7 zile.
                                </EmptyLine>
                            )}
                        </div>
                    </section>

                    <section className="rounded-lg border p-3 sm:p-3.5">
                        <h2 className="text-base font-medium">
                            Proprietăți fără contract activ
                        </h2>
                        <div className="mt-2.5 space-y-2">
                            {propertiesWithoutActiveLease.length > 0 ? (
                                propertiesWithoutActiveLease.map((property) => (
                                    <div
                                        key={property.id}
                                        className="rounded-md bg-muted/40 p-2 text-sm"
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
                                    </div>
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
                    <section className="rounded-lg border p-3 sm:p-3.5">
                        <h2 className="text-base font-medium">
                            Contracte recente
                        </h2>
                        <div className="mt-2.5 space-y-2.5">
                            {recentLeases.length > 0 ? (
                                recentLeases.map((lease) => (
                                    <div
                                        key={lease.id}
                                        className="flex items-start justify-between gap-3 text-sm"
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
                                        </div>
                                        <Badge variant="secondary">
                                            {leaseStatusLabel(lease.status)}
                                        </Badge>
                                    </div>
                                ))
                            ) : (
                                <EmptyLine>
                                    Nu există contracte recente.
                                </EmptyLine>
                            )}
                        </div>
                    </section>

                    <section className="rounded-lg border p-3 sm:p-3.5">
                        <h2 className="text-base font-medium">Plăți recente</h2>
                        <div className="mt-2.5 space-y-2.5">
                            {recentPayments.length > 0 ? (
                                recentPayments.map((payment) => (
                                    <div
                                        key={payment.id}
                                        className="flex items-start justify-between gap-3 text-sm"
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
                                        </div>
                                        <Badge variant="secondary">
                                            {paymentStatusLabel(payment.status)}
                                        </Badge>
                                    </div>
                                ))
                            ) : (
                                <EmptyLine>Nu există plăți recente.</EmptyLine>
                            )}
                        </div>
                    </section>

                    <section className="rounded-lg border p-3 sm:p-3.5">
                        <h2 className="text-base font-medium">
                            Cheltuieli recente
                        </h2>
                        <div className="mt-2.5 space-y-2.5">
                            {recentExpenses.length > 0 ? (
                                recentExpenses.map((expense) => (
                                    <div
                                        key={expense.id}
                                        className="flex items-start justify-between gap-3 text-sm"
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
                                        </div>
                                        <Badge variant="secondary">
                                            {expenseStatusLabel(expense.status)}
                                        </Badge>
                                    </div>
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
