export type UtilityServiceType =
    | 'electricity'
    | 'gas'
    | 'water'
    | 'heating'
    | 'internet'
    | 'sanitation'
    | 'other';

export type UtilityResponsibleParty = 'owner' | 'renter';
export type UtilityAccountStatus = 'active' | 'inactive';
export type UtilityBillStatus = 'unpaid' | 'paid';
export type UtilityPaidBy = 'owner' | 'renter';

export type UtilityPropertyOption = {
    id: number;
    name: string;
    city: string | null;
};

export type UtilityLeaseOption = {
    id: number;
    property_id: number;
    label: string;
};

export type UtilityAccountItem = {
    id: number;
    property_id: number;
    lease_id: number | null;
    provider_name: string;
    service_type: UtilityServiceType;
    account_identifier: string | null;
    responsible_party: UtilityResponsibleParty;
    status: UtilityAccountStatus;
    notes: string | null;
    bill_count: number;
    property: UtilityPropertyOption;
    renter_name: string | null;
};

export type UtilityBillItem = {
    id: number;
    utility_account_id: number;
    invoice_number: string;
    provider_invoice_id: string | null;
    payment_code: string | null;
    billing_period_start: string;
    billing_period_end: string;
    issue_date: string;
    due_date: string;
    amount: string;
    amount_minor: number;
    currency: string;
    status: UtilityBillStatus;
    responsible_party: UtilityResponsibleParty;
    paid_by: UtilityPaidBy | null;
    paid_on: string | null;
    notes: string | null;
    overdue: boolean;
    account: {
        provider_name: string;
        service_type: UtilityServiceType;
    };
    property: UtilityPropertyOption;
    renter_name: string | null;
    document: {
        id: number;
        original_name: string;
    } | null;
};

export type UtilitySummary = {
    active_accounts: number;
    unpaid_bills: number;
    overdue_bills: number;
    attached_bills: number;
};
