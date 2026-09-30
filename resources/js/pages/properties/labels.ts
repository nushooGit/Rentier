import { translateKey } from '@/lib/i18n';
import { currentAppLocale } from '@/lib/locale';
import { paymentPeriodLabelFromDate } from '@/pages/payments/labels';
import type {
    PropertyStatus,
    PropertyType,
    RentPaymentStatus,
    RentPaymentStatusBadge,
} from '@/types';

export const propertyTypeLabels: Record<PropertyType, string> = {
    studio: 'Garsonieră',
    apartment: 'Apartament',
    house: 'Casă',
    commercial_space: 'Spațiu comercial',
    office: 'Birou',
    other: 'Altul',
};

const propertyTypeLabelsEn: Record<PropertyType, string> = {
    studio: 'Studio',
    apartment: 'Apartment',
    house: 'House',
    commercial_space: 'Commercial space',
    office: 'Office',
    other: 'Other',
};

export const propertyStatusLabels: Record<PropertyStatus, string> = {
    available: 'Liberă',
    occupied: 'Ocupată',
    renovation: 'În renovare',
    inactive: 'Inactivă',
};

const propertyStatusLabelsEn: Record<PropertyStatus, string> = {
    available: 'Available',
    occupied: 'Occupied',
    renovation: 'Under renovation',
    inactive: 'Inactive',
};

export function propertyTypeLabel(value: PropertyType) {
    const labels =
        currentAppLocale() === 'en' ? propertyTypeLabelsEn : propertyTypeLabels;

    return labels[value] ?? value;
}

export function propertyStatusLabel(value: PropertyStatus) {
    const labels =
        currentAppLocale() === 'en'
            ? propertyStatusLabelsEn
            : propertyStatusLabels;

    return labels[value] ?? value;
}

export function propertyRentStatusBadgeLabel(
    status: RentPaymentStatus,
    badge: RentPaymentStatusBadge,
    formatMoney: (amount?: string | null, currency?: string) => string,
    currency: string,
) {
    switch (badge.key) {
        case 'partial':
            return translateKey('properties.rentStatus.partial');
        case 'arrears':
            return translateKey('properties.rentStatus.arrears', undefined, {
                amount: formatMoney(status.arrears_amount, currency),
            });
        case 'overdue_months':
            return status.overdue_month_count === 1
                ? translateKey('properties.rentStatus.oneMonthOverdue')
                : translateKey('properties.rentStatus.monthsOverdue', undefined, {
                      count: status.overdue_month_count,
                  });
        case 'paid':
            return Number(status.rent_deduction_amount ?? 0) > 0
                ? translateKey('properties.rentStatus.covered')
                : translateKey('properties.rentStatus.paid');
        case 'partial_overdue':
            return Number(status.rent_deduction_amount ?? 0) > 0
                ? translateKey('properties.rentStatus.partialCovered')
                : translateKey('properties.rentStatus.partial');
        case 'due_today':
            return translateKey('properties.rentStatus.dueToday');
        case 'upcoming':
            return status.days === 1
                ? translateKey('properties.rentStatus.upcomingOne')
                : translateKey('properties.rentStatus.upcomingMany', undefined, {
                      count: status.days ?? 0,
                  });
        case 'overdue':
            return status.days === 1
                ? translateKey('properties.rentStatus.overdueOne')
                : translateKey('properties.rentStatus.overdueMany', undefined, {
                      count: status.days ?? 0,
                  });
        default:
            return badge.key;
    }
}

export function propertyAdvanceNoticeLabel(
    notice: NonNullable<RentPaymentStatus['advance_notice']>,
    formatMoney: (amount?: string | null, currency?: string) => string,
    currency: string,
) {
    const formattedPeriod = paymentPeriodLabelFromDate(
        `${notice.period_key}-01`,
    );
    const period =
        currentAppLocale() === 'en'
            ? formattedPeriod
            : formattedPeriod.toLocaleLowerCase('ro-RO');

    if (notice.key === 'paid_through') {
        return translateKey('properties.rentStatus.paidThrough', undefined, {
            period,
        });
    }

    return translateKey('properties.rentStatus.partialAdvance', undefined, {
        period,
        amount: formatMoney(notice.amount, currency),
        expected: formatMoney(notice.expected_amount, currency),
    });
}
