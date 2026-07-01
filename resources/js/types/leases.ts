export type LeaseStatus = 'upcoming' | 'active' | 'ended';

export type LeaseOption<TValue extends string = string> = {
    value: TValue;
    label: string;
};

export type LeasePropertyOption = {
    id: number;
    name: string;
    address_line: string;
    city: string;
    monthly_rent_amount: string | null;
    currency: string;
};

export type LeaseRenter = {
    id: number;
    name: string;
    email: string | null;
    phone: string | null;
    notes: string | null;
};

export type Lease = {
    id: number;
    team_id: number;
    property_id: number;
    renter_id: number;
    start_date: string;
    end_date: string | null;
    monthly_rent_amount: string;
    currency: string;
    rent_due_day: number | null;
    deposit_amount: string | null;
    status: LeaseStatus;
    notes: string | null;
    property: LeasePropertyOption;
    renter: LeaseRenter;
    created_at: string | null;
    updated_at: string | null;
};
