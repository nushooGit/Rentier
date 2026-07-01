import { DEFAULT_LOCALE } from '@/lib/locale';

const ROMANIAN_DATE_PATTERN = /^(\d{2})\.(\d{2})\.(\d{4})$/;
const ISO_DATE_PATTERN = /^(\d{4})-(\d{2})-(\d{2})$/;

export const DATE_INPUT_FORMAT_MESSAGE =
    'Data trebuie să fie în formatul ZZ.LL.AAAA.';

function isValidDateParts(year: number, month: number, day: number) {
    const date = new Date(Date.UTC(year, month - 1, day));

    return (
        date.getUTCFullYear() === year &&
        date.getUTCMonth() === month - 1 &&
        date.getUTCDate() === day
    );
}

function parseIsoDate(date?: string | null) {
    if (!date) {
        return null;
    }

    const [year, month, day] = date.slice(0, 10).split('-');

    if (!year || !month || !day) {
        return null;
    }

    const numericYear = Number(year);
    const numericMonth = Number(month);
    const numericDay = Number(day);

    if (!isValidDateParts(numericYear, numericMonth, numericDay)) {
        return null;
    }

    return { year, month, day };
}

function createUtcDate(date?: string | null) {
    const parsedDate = parseIsoDate(date);

    if (!parsedDate) {
        return null;
    }

    return new Date(
        Date.UTC(
            Number(parsedDate.year),
            Number(parsedDate.month) - 1,
            Number(parsedDate.day),
        ),
    );
}

export function formatDateShort(
    date?: string | null,
    locale = DEFAULT_LOCALE,
) {
    if (!date) {
        return 'Nesetat';
    }

    const parsedDate = createUtcDate(date);

    if (!parsedDate) {
        return date;
    }

    if (locale === 'ro-RO') {
        const [year, month, day] = date.slice(0, 10).split('-');

        return `${day}.${month}.${year}`;
    }

    return new Intl.DateTimeFormat(locale, {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        timeZone: 'UTC',
    }).format(parsedDate);
}

export function formatDateLong(
    date?: string | null,
    locale = DEFAULT_LOCALE,
) {
    if (!date) {
        return 'Nesetat';
    }

    const parsedDate = createUtcDate(date);

    if (!parsedDate) {
        return date;
    }

    return new Intl.DateTimeFormat(locale, {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
        timeZone: 'UTC',
    }).format(parsedDate);
}

export function formatDateRangeLong(
    startDate?: string | null,
    endDate?: string | null,
    locale = DEFAULT_LOCALE,
) {
    if (!startDate) {
        return 'Nesetat';
    }

    const startLabel = formatDateLong(startDate, locale);

    if (!endDate) {
        return `Din ${startLabel}`;
    }

    return `${startLabel} - ${formatDateLong(endDate, locale)}`;
}

export function formatDateForInput(
    date?: string | null,
    locale = DEFAULT_LOCALE,
) {
    if (!date) {
        return '';
    }

    const formattedDate = formatDateShort(date, locale);

    return formattedDate === 'Nesetat' ? '' : formattedDate;
}

export function parseDateInputToIso(value: string, locale = DEFAULT_LOCALE) {
    const trimmedValue = value.trim();

    if (!trimmedValue) {
        return '';
    }

    if (locale === 'ro-RO') {
        const match = trimmedValue.match(ROMANIAN_DATE_PATTERN);

        if (!match) {
            return null;
        }

        const [, day, month, year] = match;
        const numericYear = Number(year);
        const numericMonth = Number(month);
        const numericDay = Number(day);

        if (!isValidDateParts(numericYear, numericMonth, numericDay)) {
            return null;
        }

        return `${year}-${month}-${day}`;
    }

    const isoMatch = trimmedValue.match(ISO_DATE_PATTERN);

    if (!isoMatch) {
        return null;
    }

    const [, year, month, day] = isoMatch;

    if (!isValidDateParts(Number(year), Number(month), Number(day))) {
        return null;
    }

    return trimmedValue;
}

export const formatDateForDisplay = formatDateShort;
export const formatDate = formatDateShort;
