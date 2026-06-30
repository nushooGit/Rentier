export type ExpenseCategory =
    | 'maintenance'
    | 'utilities'
    | 'taxes'
    | 'insurance'
    | 'admin'
    | 'repairs'
    | 'other';

export type ExpensePaidBy = 'landlord' | 'renter' | 'other';
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
