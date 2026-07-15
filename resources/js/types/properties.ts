export type PropertyType =
    'studio' | 'apartment' | 'house' | 'commercial_space' | 'office' | 'other';

export type PropertyStatus =
    'available' | 'occupied' | 'renovation' | 'inactive';

export type RentPaymentStatusKey =
    'paid' | 'partial' | 'due_today' | 'upcoming' | 'overdue';

export type RentPaymentStatus = {
    key: RentPaymentStatusKey;
    label: string;
    days: number | null;
    due_date: string;
    expected_amount: string;
    collected_amount: string;
    rent_deduction_amount: string;
    covered_amount: string;
    remaining_amount: string;
};

export type Property = {
    id: number;
    team_id: number;
    name: string;
    type: PropertyType;
    country: string;
    city: string;
    county_or_sector?: string | null;
    address_line: string;
    postal_code?: string | null;
    rooms?: number | null;
    usable_area_sqm?: string | null;
    floor?: number | null;
    total_floors?: number | null;
    status: PropertyStatus;
    monthly_rent_amount?: string | null;
    currency: string;
    rent_payment_status?: RentPaymentStatus | null;
    deposit_amount?: string | null;
    notes?: string | null;
    created_at?: string | null;
    updated_at?: string | null;
};

export type PropertyOption<TValue extends string = string> = {
    value: TValue;
    label: string;
};
