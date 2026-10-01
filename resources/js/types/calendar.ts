export type CalendarEventKind = 'rent_due' | 'lease_start' | 'lease_end';

export type CalendarRentStatusKey =
    | 'paid'
    | 'partial'
    | 'partial_overdue'
    | 'due_today'
    | 'upcoming'
    | 'overdue';

export type CalendarEvent = {
    id: string;
    kind: CalendarEventKind;
    date: string;
    lease_id: number;
    property_id: number;
    property_name: string;
    property_city: string;
    renter_name: string;
    amount: string | null;
    currency: string;
    remaining_amount: string | null;
    status_key: CalendarRentStatusKey | null;
};

export type CalendarSummary = {
    event_count: number;
    rent_due_count: number;
    overdue_count: number;
    lease_change_count: number;
};
