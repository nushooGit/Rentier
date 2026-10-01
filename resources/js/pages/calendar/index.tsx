import {
    Head,
    Link,
    router,
    useForm,
    usePage,
} from '@inertiajs/react';
import {
    BellRing,
    CalendarDays,
    Check,
    ChevronLeft,
    ChevronRight,
    FileText,
    Pencil,
    Plus,
    Trash2,
    WalletCards,
} from 'lucide-react';
import { useMemo, useState } from 'react';
import type { FormEvent } from 'react';
import DateInput from '@/components/date-input';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { currentIntlLocale } from '@/lib/locale';
import { formatMoney } from '@/lib/money';
import { translateKey, useI18n } from '@/lib/i18n';
import { index as calendarIndex } from '@/routes/calendar';
import { show as showLease } from '@/routes/leases';
import {
    destroy as destroyReminder,
    store as storeReminder,
    toggle as toggleReminder,
    update as updateReminder,
} from '@/routes/reminders';
import type {
    CalendarEvent,
    CalendarLeaseOption,
    CalendarPropertyOption,
    CalendarRentStatusKey,
    CalendarSummary,
} from '@/types';

type Props = {
    selectedMonth: string;
    previousMonth: string;
    nextMonth: string;
    todayMonth: string;
    today: string;
    events: CalendarEvent[];
    properties: CalendarPropertyOption[];
    leases: CalendarLeaseOption[];
    summary: CalendarSummary;
};

type ReminderFormData = {
    title: string;
    remind_on: string;
    property_id: string;
    lease_id: string;
    notes: string;
};

const selectClassName =
    'border-input bg-background ring-offset-background focus-visible:ring-ring flex h-10 w-full rounded-xl border px-3 py-1 text-sm shadow-xs transition-colors focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50';

const rentStatusClasses: Record<CalendarRentStatusKey, string> = {
    paid: 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-400/20 dark:bg-emerald-400/10 dark:text-emerald-200',
    partial:
        'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-400/20 dark:bg-amber-400/10 dark:text-amber-200',
    partial_overdue:
        'border-rose-200 bg-rose-50 text-rose-700 dark:border-rose-400/20 dark:bg-rose-400/10 dark:text-rose-200',
    due_today:
        'border-sky-200 bg-sky-50 text-sky-700 dark:border-sky-400/20 dark:bg-sky-400/10 dark:text-sky-200',
    upcoming:
        'border-slate-200 bg-slate-50 text-slate-700 dark:border-slate-400/20 dark:bg-slate-400/10 dark:text-slate-200',
    overdue:
        'border-rose-200 bg-rose-50 text-rose-700 dark:border-rose-400/20 dark:bg-rose-400/10 dark:text-rose-200',
};

function monthDate(month: string) {
    const [year, monthNumber] = month.split('-').map(Number);

    return new Date(year, monthNumber - 1, 1, 12);
}

function dateFromIso(date: string) {
    const [year, month, day] = date.split('-').map(Number);

    return new Date(year, month - 1, day, 12);
}

function formatMonthLabel(month: string) {
    return new Intl.DateTimeFormat(currentIntlLocale(), {
        month: 'long',
        year: 'numeric',
    }).format(monthDate(month));
}

function formatAgendaDate(date: string) {
    return new Intl.DateTimeFormat(currentIntlLocale(), {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
    }).format(dateFromIso(date));
}

function weekdayLabels() {
    const monday = new Date(2026, 0, 5, 12);

    return Array.from({ length: 7 }, (_, index) => {
        const date = new Date(monday);
        date.setDate(monday.getDate() + index);

        return new Intl.DateTimeFormat(currentIntlLocale(), {
            weekday: 'short',
        }).format(date);
    });
}

function eventTitle(
    event: CalendarEvent,
    t: ReturnType<typeof useI18n>['t'],
) {
    if (event.kind === 'reminder') {
        return event.title ?? t('calendar.event.reminder');
    }

    if (event.kind === 'lease_start') {
        return t('calendar.event.leaseStart');
    }

    if (event.kind === 'lease_end') {
        return t('calendar.event.leaseEnd');
    }

    return t('calendar.event.rentDue');
}

function rentStatusLabel(
    status: CalendarRentStatusKey,
    t: ReturnType<typeof useI18n>['t'],
) {
    const keys: Record<CalendarRentStatusKey, Parameters<typeof t>[0]> = {
        paid: 'calendar.status.paid',
        partial: 'calendar.status.partial',
        partial_overdue: 'calendar.status.partialOverdue',
        due_today: 'calendar.status.dueToday',
        upcoming: 'calendar.status.upcoming',
        overdue: 'calendar.status.overdue',
    };

    return t(keys[status]);
}

function CalendarEventCard({
    event,
    teamSlug,
    compact = false,
    onEditReminder,
}: {
    event: CalendarEvent;
    teamSlug: string;
    compact?: boolean;
    onEditReminder: (event: CalendarEvent) => void;
}) {
    const { t } = useI18n();
    const isRent = event.kind === 'rent_due';
    const isReminder = event.kind === 'reminder';
    const details = [event.property_name, event.renter_name]
        .filter(Boolean)
        .join(' · ');

    const content = (
        <div className="flex min-w-0 items-start gap-2">
            <span
                className={
                    isReminder
                        ? 'mt-0.5 flex size-7 shrink-0 items-center justify-center rounded-lg bg-violet-500/10 text-violet-700 dark:text-violet-300'
                        : isRent
                          ? 'mt-0.5 flex size-7 shrink-0 items-center justify-center rounded-lg bg-emerald-500/10 text-emerald-700 dark:text-emerald-300'
                          : 'mt-0.5 flex size-7 shrink-0 items-center justify-center rounded-lg bg-sky-500/10 text-sky-700 dark:text-sky-300'
                }
            >
                {isReminder ? (
                    <BellRing className="size-3.5" aria-hidden="true" />
                ) : isRent ? (
                    <WalletCards className="size-3.5" aria-hidden="true" />
                ) : (
                    <FileText className="size-3.5" aria-hidden="true" />
                )}
            </span>

            <div className="min-w-0 flex-1">
                <div className="flex min-w-0 flex-wrap items-center gap-1.5">
                    <p
                        className={
                            compact
                                ? 'truncate text-[11px] font-semibold'
                                : 'truncate text-sm font-semibold'
                        }
                    >
                        {eventTitle(event, t)}
                    </p>

                    {isRent && event.status_key ? (
                        <Badge
                            variant="outline"
                            className={`h-5 px-1.5 text-[10px] ${rentStatusClasses[event.status_key]}`}
                        >
                            {rentStatusLabel(event.status_key, t)}
                        </Badge>
                    ) : null}

                    {isReminder && event.completed ? (
                        <Badge
                            variant="outline"
                            className="h-5 border-emerald-200 bg-emerald-50 px-1.5 text-[10px] text-emerald-700 dark:border-emerald-400/20 dark:bg-emerald-400/10 dark:text-emerald-200"
                        >
                            {t('calendar.reminder.completed')}
                        </Badge>
                    ) : null}
                </div>

                {details !== '' ? (
                    <p
                        className={
                            compact
                                ? 'truncate text-[10px] text-muted-foreground'
                                : 'mt-0.5 truncate text-xs text-muted-foreground'
                        }
                    >
                        {details}
                    </p>
                ) : null}

                {isReminder && event.notes && !compact ? (
                    <p className="mt-2 line-clamp-2 text-xs text-muted-foreground">
                        {event.notes}
                    </p>
                ) : null}

                {isRent &&
                event.amount !== null &&
                event.currency !== null ? (
                    <div
                        className={
                            compact
                                ? 'mt-1 text-[10px] font-medium'
                                : 'mt-2 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs'
                        }
                    >
                        <span>
                            {formatMoney(event.amount, event.currency)}
                        </span>
                        {event.remaining_amount !== null &&
                        Number(event.remaining_amount) > 0 ? (
                            <span className="text-muted-foreground">
                                {t('calendar.remaining', {
                                    amount: formatMoney(
                                        event.remaining_amount,
                                        event.currency,
                                    ),
                                })}
                            </span>
                        ) : null}
                    </div>
                ) : null}
            </div>
        </div>
    );

    const className = compact
        ? 'block w-full min-w-0 rounded-lg border border-border/70 bg-background/80 px-2 py-1.5 text-left transition hover:border-primary/30 hover:bg-muted/60 focus:outline-none focus-visible:ring-2 focus-visible:ring-ring'
        : 'block w-full rounded-xl border border-border/70 bg-card/90 p-3 text-left shadow-sm transition hover:border-primary/30 hover:bg-muted/40 focus:outline-none focus-visible:ring-2 focus-visible:ring-ring';

    if (isReminder) {
        return (
            <button
                type="button"
                className={className}
                onClick={() => onEditReminder(event)}
                aria-label={t('calendar.reminder.edit')}
                data-test="calendar-event"
            >
                {content}
            </button>
        );
    }

    if (event.lease_id === null) {
        return <div className={className}>{content}</div>;
    }

    return (
        <Link
            href={showLease([teamSlug, event.lease_id])}
            className={className}
            aria-label={t('calendar.viewLease', {
                name: event.renter_name ?? '',
            })}
            data-test="calendar-event"
        >
            {content}
        </Link>
    );
}

function ReminderDialog({
    open,
    onOpenChange,
    reminder,
    defaultDate,
    teamSlug,
    properties,
    leases,
}: {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    reminder: CalendarEvent | null;
    defaultDate: string;
    teamSlug: string;
    properties: CalendarPropertyOption[];
    leases: CalendarLeaseOption[];
}) {
    const { locale, t } = useI18n();
    const form = useForm<ReminderFormData>({
        title: reminder?.title ?? '',
        remind_on: reminder?.date ?? defaultDate,
        property_id: reminder?.property_id?.toString() ?? '',
        lease_id: reminder?.lease_id?.toString() ?? '',
        notes: reminder?.notes ?? '',
    });

    const filteredLeases = useMemo(() => {
        if (form.data.property_id === '') {
            return leases;
        }

        return leases.filter(
            (lease) =>
                lease.property_id.toString() === form.data.property_id,
        );
    }, [form.data.property_id, leases]);

    const closeDialog = () => {
        form.clearErrors();
        form.reset();
        onOpenChange(false);
    };

    const submit = (event: FormEvent) => {
        event.preventDefault();

        const options = {
            preserveScroll: true,
            onSuccess: closeDialog,
        };

        if (reminder?.reminder_id) {
            form.put(
                updateReminder([teamSlug, reminder.reminder_id]).url,
                options,
            );

            return;
        }

        form.post(storeReminder(teamSlug).url, options);
    };

    const toggleComplete = () => {
        if (!reminder?.reminder_id) {
            return;
        }

        router.patch(
            toggleReminder([teamSlug, reminder.reminder_id]).url,
            {},
            {
                preserveScroll: true,
                onSuccess: closeDialog,
            },
        );
    };

    const deleteReminder = () => {
        if (
            !reminder?.reminder_id ||
            !window.confirm(t('calendar.reminder.deleteConfirm'))
        ) {
            return;
        }

        router.delete(
            destroyReminder([teamSlug, reminder.reminder_id]).url,
            {
                preserveScroll: true,
                onSuccess: closeDialog,
            },
        );
    };

    return (
        <Dialog
            open={open}
            onOpenChange={(nextOpen) => {
                if (!nextOpen) {
                    closeDialog();

                    return;
                }

                onOpenChange(true);
            }}
        >
            <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-xl">
                <DialogHeader>
                    <DialogTitle>
                        {reminder
                            ? t('calendar.reminder.edit')
                            : t('calendar.reminder.add')}
                    </DialogTitle>
                    <DialogDescription>
                        {t('calendar.reminder.formDescription')}
                    </DialogDescription>
                </DialogHeader>

                <form onSubmit={submit} className="grid gap-4">
                    <div className="grid gap-1.5">
                        <Label htmlFor="reminder-title">
                            {t('calendar.reminder.title')}
                        </Label>
                        <Input
                            id="reminder-title"
                            value={form.data.title}
                            onChange={(event) =>
                                form.setData('title', event.target.value)
                            }
                            placeholder={t(
                                'calendar.reminder.titlePlaceholder',
                            )}
                            maxLength={191}
                            required
                            autoFocus
                            data-test="reminder-title-input"
                        />
                        <InputError message={form.errors.title} />
                    </div>

                    <div className="grid gap-1.5">
                        <Label htmlFor="reminder-date">
                            {t('calendar.reminder.date')}
                        </Label>
                        <DateInput
                            key={form.data.remind_on}
                            id="reminder-date"
                            name="remind_on"
                            defaultValue={form.data.remind_on}
                            locale={locale === 'ro' ? 'ro-RO' : 'en-US'}
                            onValueChange={(value) =>
                                form.setData('remind_on', value)
                            }
                            error={form.errors.remind_on}
                            required
                            data-test="reminder-date-input"
                        />
                    </div>

                    <div className="grid gap-3 sm:grid-cols-2">
                        <div className="grid gap-1.5">
                            <Label htmlFor="reminder-property">
                                {t('calendar.reminder.property')}
                            </Label>
                            <select
                                id="reminder-property"
                                className={selectClassName}
                                value={form.data.property_id}
                                onChange={(event) => {
                                    const propertyId = event.target.value;
                                    form.setData((data) => ({
                                        ...data,
                                        property_id: propertyId,
                                        lease_id:
                                            data.lease_id !== '' &&
                                            leases.find(
                                                (lease) =>
                                                    lease.id.toString() ===
                                                    data.lease_id,
                                            )?.property_id.toString() !==
                                                propertyId
                                                ? ''
                                                : data.lease_id,
                                    }));
                                }}
                                data-test="reminder-property-select"
                            >
                                <option value="">
                                    {t('calendar.reminder.none')}
                                </option>
                                {properties.map((property) => (
                                    <option
                                        key={property.id}
                                        value={property.id}
                                    >
                                        {property.name}
                                        {property.city
                                            ? ` · ${property.city}`
                                            : ''}
                                    </option>
                                ))}
                            </select>
                            <InputError message={form.errors.property_id} />
                        </div>

                        <div className="grid gap-1.5">
                            <Label htmlFor="reminder-lease">
                                {t('calendar.reminder.lease')}
                            </Label>
                            <select
                                id="reminder-lease"
                                className={selectClassName}
                                value={form.data.lease_id}
                                onChange={(event) => {
                                    const leaseId = event.target.value;
                                    const lease = leases.find(
                                        (candidate) =>
                                            candidate.id.toString() === leaseId,
                                    );

                                    form.setData((data) => ({
                                        ...data,
                                        lease_id: leaseId,
                                        property_id: lease
                                            ? lease.property_id.toString()
                                            : data.property_id,
                                    }));
                                }}
                                data-test="reminder-lease-select"
                            >
                                <option value="">
                                    {t('calendar.reminder.none')}
                                </option>
                                {filteredLeases.map((lease) => (
                                    <option key={lease.id} value={lease.id}>
                                        {lease.label}
                                    </option>
                                ))}
                            </select>
                            <InputError message={form.errors.lease_id} />
                        </div>
                    </div>

                    <p className="-mt-1 text-xs text-muted-foreground">
                        {t('calendar.reminder.associationHelp')}
                    </p>

                    <div className="grid gap-1.5">
                        <Label htmlFor="reminder-notes">
                            {t('calendar.reminder.notes')}
                        </Label>
                        <textarea
                            id="reminder-notes"
                            value={form.data.notes}
                            onChange={(event) =>
                                form.setData('notes', event.target.value)
                            }
                            placeholder={t(
                                'calendar.reminder.notesPlaceholder',
                            )}
                            rows={4}
                            maxLength={5000}
                            className="border-input bg-background ring-offset-background focus-visible:ring-ring min-h-24 w-full resize-y rounded-xl border px-3 py-2 text-sm shadow-xs transition-colors focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none"
                            data-test="reminder-notes-input"
                        />
                        <InputError message={form.errors.notes} />
                    </div>

                    <DialogFooter className="gap-2 sm:justify-between">
                        <div className="flex flex-wrap gap-2">
                            {reminder?.reminder_id ? (
                                <>
                                    <Button
                                        type="button"
                                        variant="outline"
                                        onClick={toggleComplete}
                                        data-test="reminder-toggle-button"
                                    >
                                        <Check />
                                        {reminder.completed
                                            ? t('calendar.reminder.reopen')
                                            : t('calendar.reminder.done')}
                                    </Button>
                                    <Button
                                        type="button"
                                        variant="destructive"
                                        onClick={deleteReminder}
                                        data-test="reminder-delete-button"
                                    >
                                        <Trash2 />
                                        {t('calendar.reminder.delete')}
                                    </Button>
                                </>
                            ) : null}
                        </div>

                        <div className="flex gap-2">
                            <Button
                                type="button"
                                variant="outline"
                                onClick={closeDialog}
                            >
                                {t('common.cancel')}
                            </Button>
                            <Button
                                type="submit"
                                disabled={form.processing}
                                data-test="reminder-save-button"
                            >
                                {reminder ? <Pencil /> : <Plus />}
                                {reminder
                                    ? t('calendar.reminder.update')
                                    : t('calendar.reminder.create')}
                            </Button>
                        </div>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

export default function CalendarIndex({
    selectedMonth,
    previousMonth,
    nextMonth,
    todayMonth,
    today,
    events,
    properties,
    leases,
    summary,
}: Props) {
    const { currentTeam } = usePage().props;
    const { t } = useI18n();
    const currentTeamSlug = currentTeam?.slug ?? '';
    const [dialogOpen, setDialogOpen] = useState(false);
    const [editingReminder, setEditingReminder] =
        useState<CalendarEvent | null>(null);

    const eventsByDate = useMemo(() => {
        const grouped = new Map<string, CalendarEvent[]>();

        for (const event of events) {
            grouped.set(event.date, [...(grouped.get(event.date) ?? []), event]);
        }

        return grouped;
    }, [events]);

    const agendaDates = useMemo(
        () => Array.from(eventsByDate.keys()).sort(),
        [eventsByDate],
    );

    const calendarCells = useMemo(() => {
        const [year, monthNumber] = selectedMonth.split('-').map(Number);
        const daysInMonth = new Date(year, monthNumber, 0).getDate();
        const mondayOffset =
            (new Date(year, monthNumber - 1, 1, 12).getDay() + 6) % 7;
        const cellCount = Math.ceil((mondayOffset + daysInMonth) / 7) * 7;

        return Array.from({ length: cellCount }, (_, index) => {
            const day = index - mondayOffset + 1;

            if (day < 1 || day > daysInMonth) {
                return null;
            }

            return `${selectedMonth}-${String(day).padStart(2, '0')}`;
        });
    }, [selectedMonth]);

    const calendarBaseUrl = calendarIndex(currentTeamSlug).url;
    const monthHref = (month: string) =>
        `${calendarBaseUrl}?month=${encodeURIComponent(month)}`;
    const defaultReminderDate =
        selectedMonth === todayMonth ? today : `${selectedMonth}-01`;

    const openNewReminder = () => {
        setEditingReminder(null);
        setDialogOpen(true);
    };

    const openReminder = (event: CalendarEvent) => {
        setEditingReminder(event);
        setDialogOpen(true);
    };

    return (
        <>
            <Head title={t('calendar.title')} />

            <div className="mx-auto flex w-full max-w-[1480px] flex-col gap-5 p-3 sm:p-5 lg:p-6">
                <div className="flex flex-col gap-4 rounded-2xl border border-border/70 bg-card/75 p-4 shadow-sm sm:flex-row sm:items-center sm:justify-between sm:p-5">
                    <Heading
                        variant="small"
                        title={t('calendar.title')}
                        description={t('calendar.description')}
                    />

                    <div className="flex flex-wrap items-center gap-2">
                        <Button
                            type="button"
                            size="sm"
                            onClick={openNewReminder}
                            data-test="add-reminder-button"
                        >
                            <Plus />
                            {t('calendar.reminder.add')}
                        </Button>
                        <Button variant="outline" size="sm" asChild>
                            <Link
                                href={monthHref(previousMonth)}
                                aria-label={t('calendar.previousMonth')}
                            >
                                <ChevronLeft />
                                <span className="sr-only sm:not-sr-only">
                                    {t('calendar.previousMonth')}
                                </span>
                            </Link>
                        </Button>
                        <Button variant="outline" size="sm" asChild>
                            <Link href={monthHref(todayMonth)}>
                                {t('calendar.today')}
                            </Link>
                        </Button>
                        <Button variant="outline" size="sm" asChild>
                            <Link
                                href={monthHref(nextMonth)}
                                aria-label={t('calendar.nextMonth')}
                            >
                                <span className="sr-only sm:not-sr-only">
                                    {t('calendar.nextMonth')}
                                </span>
                                <ChevronRight />
                            </Link>
                        </Button>
                    </div>
                </div>

                <section className="rounded-3xl border border-slate-800 bg-[#0b111c] px-5 py-5 text-white shadow-sm sm:px-7">
                    <div className="flex items-center gap-3">
                        <span className="flex size-10 items-center justify-center rounded-xl border border-sky-400/25 bg-sky-400/10 text-sky-300">
                            <CalendarDays className="size-5" aria-hidden="true" />
                        </span>
                        <div>
                            <p className="text-[10px] font-semibold uppercase tracking-[0.2em] text-sky-300">
                                {t('calendar.agenda')}
                            </p>
                            <h1 className="mt-0.5 text-2xl font-semibold tracking-[-0.03em] capitalize sm:text-3xl">
                                {formatMonthLabel(selectedMonth)}
                            </h1>
                        </div>
                    </div>
                </section>

                <div className="grid grid-cols-2 gap-2.5 lg:grid-cols-5">
                    {[
                        {
                            label: t('calendar.summary.events'),
                            value: summary.event_count,
                            detail: null,
                        },
                        {
                            label: t('calendar.summary.rentDue'),
                            value: summary.rent_due_count,
                            detail: null,
                        },
                        {
                            label: t('calendar.summary.overdue'),
                            value: summary.overdue_count,
                            detail: null,
                        },
                        {
                            label: t('calendar.summary.leaseChanges'),
                            value: summary.lease_change_count,
                            detail: null,
                        },
                        {
                            label: t('calendar.summary.reminders'),
                            value: summary.reminder_count,
                            detail: t('calendar.summary.openReminders', {
                                count: summary.open_reminder_count,
                            }),
                        },
                    ].map(({ label, value, detail }) => (
                        <section
                            key={label}
                            className="rounded-2xl border border-border/70 bg-card p-4 shadow-sm"
                        >
                            <p className="text-xs font-semibold uppercase tracking-[0.12em] text-muted-foreground">
                                {label}
                            </p>
                            <p className="mt-2 text-2xl font-semibold tracking-tight">
                                {value}
                            </p>
                            {detail ? (
                                <p className="mt-1 text-xs text-muted-foreground">
                                    {detail}
                                </p>
                            ) : null}
                        </section>
                    ))}
                </div>

                <section className="hidden overflow-hidden rounded-2xl border border-border/70 bg-card shadow-sm md:block">
                    <div className="grid grid-cols-7 border-b border-border/70 bg-muted/35">
                        {weekdayLabels().map((label) => (
                            <div
                                key={label}
                                className="px-3 py-2 text-xs font-semibold capitalize text-muted-foreground"
                            >
                                {label}
                            </div>
                        ))}
                    </div>

                    <div className="grid grid-cols-7">
                        {calendarCells.map((date, index) => {
                            if (date === null) {
                                return (
                                    <div
                                        key={`empty-${index}`}
                                        className="min-h-32 border-r border-b border-border/50 bg-muted/10 last:border-r-0"
                                    />
                                );
                            }

                            const dayEvents = eventsByDate.get(date) ?? [];
                            const isToday = date === today;

                            return (
                                <div
                                    key={date}
                                    className="min-h-32 border-r border-b border-border/50 p-2 last:border-r-0"
                                    data-test="calendar-day"
                                >
                                    <div className="mb-2 flex items-center justify-between">
                                        <span
                                            className={
                                                isToday
                                                    ? 'flex size-7 items-center justify-center rounded-full bg-primary text-xs font-semibold text-primary-foreground'
                                                    : 'flex size-7 items-center justify-center text-xs font-medium text-muted-foreground'
                                            }
                                        >
                                            {Number(date.slice(-2))}
                                        </span>
                                    </div>

                                    <div className="space-y-1.5">
                                        {dayEvents.map((event) => (
                                            <CalendarEventCard
                                                key={event.id}
                                                event={event}
                                                teamSlug={currentTeamSlug}
                                                compact
                                                onEditReminder={openReminder}
                                            />
                                        ))}
                                    </div>
                                </div>
                            );
                        })}
                    </div>
                </section>

                <section className="space-y-3 md:hidden">
                    {agendaDates.length > 0 ? (
                        agendaDates.map((date) => (
                            <div
                                key={date}
                                className="rounded-2xl border border-border/70 bg-card p-3 shadow-sm"
                            >
                                <div className="mb-2 flex items-center justify-between gap-3">
                                    <h2 className="text-sm font-semibold capitalize">
                                        {formatAgendaDate(date)}
                                    </h2>
                                    {date === today ? (
                                        <Badge variant="secondary">
                                            {t('calendar.todayDate')}
                                        </Badge>
                                    ) : null}
                                </div>
                                <div className="space-y-2">
                                    {(eventsByDate.get(date) ?? []).map(
                                        (event) => (
                                            <CalendarEventCard
                                                key={event.id}
                                                event={event}
                                                teamSlug={currentTeamSlug}
                                                onEditReminder={openReminder}
                                            />
                                        ),
                                    )}
                                </div>
                            </div>
                        ))
                    ) : (
                        <div className="rounded-2xl border border-dashed border-border bg-card/50 p-8 text-center shadow-sm">
                            <CalendarDays className="mx-auto size-8 text-muted-foreground" />
                            <p className="mt-3 text-sm text-muted-foreground">
                                {t('calendar.empty')}
                            </p>
                        </div>
                    )}
                </section>
            </div>

            <ReminderDialog
                key={
                    editingReminder?.reminder_id
                        ? `reminder-${editingReminder.reminder_id}`
                        : `new-${defaultReminderDate}-${dialogOpen ? 'open' : 'closed'}`
                }
                open={dialogOpen}
                onOpenChange={setDialogOpen}
                reminder={editingReminder}
                defaultDate={defaultReminderDate}
                teamSlug={currentTeamSlug}
                properties={properties}
                leases={leases}
            />
        </>
    );
}

CalendarIndex.layout = (props: {
    currentTeam?: { slug: string } | null;
}) => ({
    breadcrumbs: [
        {
            title: translateKey('nav.calendar'),
            href: props.currentTeam ? calendarIndex(props.currentTeam.slug) : '/',
        },
    ],
});
