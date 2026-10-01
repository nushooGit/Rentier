import { Head, Link, usePage } from '@inertiajs/react';
import {
    CalendarDays,
    ChevronLeft,
    ChevronRight,
    FileText,
    WalletCards,
} from 'lucide-react';
import { useMemo } from 'react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { currentIntlLocale } from '@/lib/locale';
import { formatMoney } from '@/lib/money';
import { translateKey, useI18n } from '@/lib/i18n';
import { index as calendarIndex } from '@/routes/calendar';
import { show as showLease } from '@/routes/leases';
import type {
    CalendarEvent,
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
    summary: CalendarSummary;
};

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

function eventTitle(event: CalendarEvent, t: ReturnType<typeof useI18n>['t']) {
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
}: {
    event: CalendarEvent;
    teamSlug: string;
    compact?: boolean;
}) {
    const { t } = useI18n();
    const isRent = event.kind === 'rent_due';

    return (
        <Link
            href={showLease([teamSlug, event.lease_id])}
            className={
                compact
                    ? 'block min-w-0 rounded-lg border border-border/70 bg-background/80 px-2 py-1.5 text-left transition hover:border-primary/30 hover:bg-muted/60 focus:outline-none focus-visible:ring-2 focus-visible:ring-ring'
                    : 'block rounded-xl border border-border/70 bg-card/90 p-3 shadow-sm transition hover:border-primary/30 hover:bg-muted/40 focus:outline-none focus-visible:ring-2 focus-visible:ring-ring'
            }
            aria-label={t('calendar.viewLease', { name: event.renter_name })}
            data-test="calendar-event"
        >
            <div className="flex min-w-0 items-start gap-2">
                <span
                    className={
                        isRent
                            ? 'mt-0.5 flex size-7 shrink-0 items-center justify-center rounded-lg bg-emerald-500/10 text-emerald-700 dark:text-emerald-300'
                            : 'mt-0.5 flex size-7 shrink-0 items-center justify-center rounded-lg bg-sky-500/10 text-sky-700 dark:text-sky-300'
                    }
                >
                    {isRent ? (
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
                    </div>

                    <p
                        className={
                            compact
                                ? 'truncate text-[10px] text-muted-foreground'
                                : 'mt-0.5 truncate text-xs text-muted-foreground'
                        }
                    >
                        {event.property_name} · {event.renter_name}
                    </p>

                    {isRent && event.amount !== null ? (
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
        </Link>
    );
}

export default function CalendarIndex({
    selectedMonth,
    previousMonth,
    nextMonth,
    todayMonth,
    today,
    events,
    summary,
}: Props) {
    const { currentTeam } = usePage().props;
    const { t } = useI18n();
    const currentTeamSlug = currentTeam?.slug ?? '';

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

                <div className="grid grid-cols-2 gap-2.5 lg:grid-cols-4">
                    {[
                        [t('calendar.summary.events'), summary.event_count],
                        [t('calendar.summary.rentDue'), summary.rent_due_count],
                        [t('calendar.summary.overdue'), summary.overdue_count],
                        [
                            t('calendar.summary.leaseChanges'),
                            summary.lease_change_count,
                        ],
                    ].map(([label, value]) => (
                        <section
                            key={String(label)}
                            className="rounded-2xl border border-border/70 bg-card p-4 shadow-sm"
                        >
                            <p className="text-xs font-semibold uppercase tracking-[0.12em] text-muted-foreground">
                                {label}
                            </p>
                            <p className="mt-2 text-2xl font-semibold tracking-tight">
                                {value}
                            </p>
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
