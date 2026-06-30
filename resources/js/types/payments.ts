export type PaymentMethod = 'cash' | 'bank_transfer' | 'card' | 'other';
export type PaymentStatus = 'paid' | 'partial' | 'pending' | 'cancelled';

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
    payment_date: string;
    period_month: number;
    period_year: number;
    method: PaymentMethod | null;
    status: PaymentStatus;
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
