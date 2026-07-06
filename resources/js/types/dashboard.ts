export type DashboardSummary = {
    property_count: number;
    active_lease_count: number;
    estimated_monthly_rent: string;
    current_month_payments: string;
    current_month_rent_deductions: string;
    current_month_covered_rent: string;
    remaining_rent: string;
    expected_guarantees: string;
    collected_guarantees: string;
    remaining_guarantees: string;
    overdue_count: number;
    occupancy_label: string;
    occupancy_rate: number;
    current_month_expenses: string;
    current_month_profit: string;
    operational_cash_result: string;
    tenant_reimbursement_expenses: string;
    utility_deduction_expenses: string;
    unsettled_tenant_paid_owner_expenses: string;
    recoverable_expenses: string;
    total_receivable: string;
    currency: string;
};

export type DashboardPaymentMethodBreakdown = {
    method: string | null;
    label: string;
    amount: string;
    currency: string;
};

export type DashboardLeaseFinancialRow = {
    lease_id: number;
    property_id: number;
    property_name: string;
    renter_name: string;
    monthly_rent_amount: string;
    currency: string;
    rent_due_day: number;
    due_date: string;
    status_key: 'paid' | 'partial' | 'due_today' | 'upcoming' | 'overdue';
    status_label: string;
    days: number | null;
    expected_amount: string;
    collected_amount: string;
    rent_deduction_amount: string;
    covered_amount: string;
    remaining_amount: string;
};

export type DashboardPropertyWithoutActiveLease = {
    id: number;
    name: string;
    city: string;
    address_line: string;
    monthly_rent_amount: string | null;
    currency: string;
};

export type DashboardRecentLease = {
    id: number;
    renter_name: string;
    property_name: string;
    status: 'upcoming' | 'active' | 'ended' | 'cancelled';
    start_date: string;
    monthly_rent_amount: string;
    currency: string;
};

export type DashboardRecentPayment = {
    id: number;
    renter_name: string;
    property_name: string;
    amount: string;
    currency: string;
    payment_type: 'rent' | 'guarantee';
    payment_date: string;
    status: 'paid' | 'partial' | 'pending' | 'cancelled';
};

export type DashboardRecentExpense = {
    id: number;
    title: string;
    property_name: string;
    amount: string;
    currency: string;
    expense_date: string;
    status: 'paid' | 'pending' | 'reimbursable' | 'cancelled';
};
