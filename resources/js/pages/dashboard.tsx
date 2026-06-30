import { Head } from '@inertiajs/react';
import { useState } from 'react';
import Heading from '@/components/heading';
import PendingInvitationsModal from '@/components/pending-invitations-modal';
import { Badge } from '@/components/ui/badge';
import { expenseStatusLabel } from '@/pages/expenses/labels';
import { leaseStatusLabel } from '@/pages/leases/labels';
import { paymentStatusLabel } from '@/pages/payments/labels';
import { dashboard } from '@/routes';
import type { DashboardInvitation } from '@/types';

type DashboardSummary = {
    property_count: number;
    active_lease_count: number;
    estimated_monthly_rent: string;
    current_month_payments: string;
    current_month_expenses: string;
    current_month_profit: string;
    currency: string;
};

type RecentLease = {
    id: number;
    renter_name: string;
    property_name: string;
    status: 'upcoming' | 'active' | 'ended' | 'cancelled';
    start_date: string;
    monthly_rent_amount: string;
    currency: string;
};

type RecentPayment = {
    id: number;
    renter_name: string;
    property_name: string;
    amount: string;
    currency: string;
    payment_date: string;
    status: 'paid' | 'partial' | 'pending' | 'cancelled';
};

type RecentExpense = {
    id: number;
    title: string;
    property_name: string;
    amount: string;
    currency: string;
    expense_date: string;
    status: 'paid' | 'pending' | 'reimbursable' | 'cancelled';
};

type Props = {
    pendingInvitations?: DashboardInvitation[];
    summary: DashboardSummary;
    propertyStatusSummary: {
        active: number;
        available: number;
    };
    recentLeases: RecentLease[];
    recentPayments: RecentPayment[];
    recentExpenses: RecentExpense[];
};

function formatMoney(amount?: string | null, currency = 'RON') {
    if (!amount) {
        return `0 ${currency}`;
    }

    return `${Number(amount).toLocaleString(undefined, {
        maximumFractionDigits: 2,
        minimumFractionDigits: 0,
    })} ${currency}`;
}

function SummaryCard({
    label,
    value,
}: {
    label: string;
    value: string | number;
}) {
    return (
        <section className="rounded-lg border p-3 sm:p-3.5">
            <p className="text-xs text-muted-foreground">{label}</p>
            <p className="mt-1 text-lg font-semibold">{value}</p>
        </section>
    );
}

function EmptyLine({ children }: { children: string }) {
    return <p className="text-sm text-muted-foreground">{children}</p>;
}

export default function Dashboard({
    pendingInvitations = [],
    summary,
    propertyStatusSummary,
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

                <div className="grid gap-2.5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
                    <SummaryCard
                        label="Total proprietăți"
                        value={summary.property_count}
                    />
                    <SummaryCard
                        label="Contracte active"
                        value={summary.active_lease_count}
                    />
                    <SummaryCard
                        label="Chirie lunară estimată"
                        value={formatMoney(
                            summary.estimated_monthly_rent,
                            summary.currency,
                        )}
                    />
                    <SummaryCard
                        label="Plăți încasate luna curentă"
                        value={formatMoney(
                            summary.current_month_payments,
                            summary.currency,
                        )}
                    />
                    <SummaryCard
                        label="Cheltuieli luna curentă"
                        value={formatMoney(
                            summary.current_month_expenses,
                            summary.currency,
                        )}
                    />
                    <SummaryCard
                        label="Profit estimat luna curentă"
                        value={formatMoney(
                            summary.current_month_profit,
                            summary.currency,
                        )}
                    />
                </div>

                <section className="rounded-lg border p-3 sm:p-3.5">
                    <h2 className="text-base font-medium">
                        Proprietăți active/libere
                    </h2>
                    <div className="mt-2.5 grid gap-2 text-sm sm:grid-cols-2">
                        <div className="rounded-md bg-muted/40 p-2">
                            Active: {propertyStatusSummary.active}
                        </div>
                        <div className="rounded-md bg-muted/40 p-2">
                            Libere: {propertyStatusSummary.available}
                        </div>
                    </div>
                </section>

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
