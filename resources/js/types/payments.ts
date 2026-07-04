export type PaymentMethod = 'cash' | 'bank_transfer' | 'card' | 'other';
export type PaymentStatus = 'paid' | 'partial' | 'pending' | 'cancelled';
export type PaymentType = 'rent' | 'guarantee';

export type PaymentOption<TValue extends string = string> = {
    value: TValue;
    label: string;
};

export type PaymentLeaseOption = {
    id: number;
    label: string;
    property: string;
    renter: string;
    monthly_rent_amount: string;
    deposit_amount: string | null;
    currency: string;
};

export type RentPayment = {
    id: number;
    team_id: number;
    lease_id: number;
    property_id: number;
    renter_id: number;
    amount: string;
    currency: string;
    payment_type: PaymentType;
    payment_date: string;
    period_month: number | null;
    period_year: number | null;
    method: PaymentMethod | null;
    status: PaymentStatus;
    status_summary: {
        expected_amount: string;
        collected_amount: string;
        remaining_amount: string;
        status_key: 'paid' | 'partial' | 'pending' | 'not_configured' | 'unpaid';
        status_label: string;
    };
    guarantee_summary: {
        expected_amount: string;
        collected_amount: string;
        remaining_amount: string;
        status_key: 'not_configured' | 'paid' | 'partial' | 'unpaid';
        status_label: string;
    } | null;
    notes: string | null;
    lease: {
        id: number;
        status: string;
        start_date: string;
        end_date: string | null;
    };
    property: {
        id: number;
        name: string;
        city: string;
    };
    renter: {
        id: number;
        name: string;
    };
    created_at: string | null;
    updated_at: string | null;
};
