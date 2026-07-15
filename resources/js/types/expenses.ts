export type ExpenseCategory =
    'repairs' | 'maintenance' | 'utilities' | 'renovation' | 'taxes' | 'other';

export type ExpenseParty = 'owner' | 'tenant';
export type ExpensePaidBy = ExpenseParty;
export type ExpenseResponsibleParty = ExpenseParty;
export type ExpenseSettlementType =
    'none' | 'deduct_from_rent' | 'deduct_from_utilities' | 'reimburse';
export type ExpenseStatus = 'paid' | 'pending' | 'reimbursable' | 'cancelled';

export type ExpenseOption<TValue extends string = string> = {
    value: TValue;
    label: string;
};

export type ExpensePropertyOption = {
    id: number;
    name: string;
    city: string;
};

export type ExpenseLeaseOption = {
    id: number;
    property_id: number;
    label: string;
    start_date: string;
    end_date: string | null;
};

export type Expense = {
    id: number;
    team_id: number;
    property_id: number;
    lease_id: number | null;
    title: string;
    category: ExpenseCategory;
    amount: string;
    currency: string;
    expense_date: string;
    paid_by: ExpensePaidBy;
    responsible_party: ExpenseResponsibleParty;
    settlement_type: ExpenseSettlementType;
    settled_at: string | null;
    settlement_state: {
        kind:
            | 'none'
            | 'reimbursement_due'
            | 'reimbursed'
            | 'recovery_due'
            | 'recovered';
        label: string | null;
        action_label: string | null;
        action_route: string | null;
        settled_label: string | null;
    };
    affects_owner_profit: boolean;
    status: ExpenseStatus;
    notes: string | null;
    property: {
        id: number;
        name: string;
        city: string;
    };
    lease: {
        id: number;
        renter: {
            id: number;
            name: string;
        };
    } | null;
    created_at: string | null;
    updated_at: string | null;
};

export type ExpenseSummary = {
    total: string;
    owner_supported: string;
    tenant_supported: string;
    owner_paid: string;
    tenant_paid: string;
    by_category: Record<ExpenseCategory, string>;
};
