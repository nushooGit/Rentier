export type CalendarEventKind =
    | 'rent_due'
    | 'lease_start'
    | 'lease_end'
    | 'reminder';

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
    reminder_id: number | null;
    lease_id: number | null;
    property_id: number | null;
    property_name: string | null;
    property_city: string | null;
    renter_name: string | null;
    title: string | null;
    notes: string | null;
    completed: boolean;
    amount: string | null;
    currency: string | null;
    remaining_amount: string | null;
    status_key: CalendarRentStatusKey | null;
};

export type CalendarSummary = {
    event_count: number;
    rent_due_count: number;
    overdue_count: number;
    lease_change_count: number;
    reminder_count: number;
    open_reminder_count: number;
};

export type CalendarPropertyOption = {
    id: number;
    name: string;
    city: string | null;
};

export type CalendarLeaseOption = {
    id: number;
    property_id: number;
    label: string;
};
