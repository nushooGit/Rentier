import { currentAppLocale, currentIntlLocale } from '@/lib/locale';
import type { PaymentMethod, PaymentStatus, PaymentType } from '@/types';

type PaymentSummaryStatus =
    | 'paid'
    | 'partial'
    | 'pending'
    | 'not_configured'
    | 'unpaid';

export const paymentTypeLabels: Record<PaymentType, string> = {
    rent: 'Chirie',
    guarantee: 'Garanție',
};

const paymentTypeLabelsEn: Record<PaymentType, string> = {
    rent: 'Rent',
    guarantee: 'Deposit',
};

export const paymentStatusLabels: Record<PaymentStatus, string> = {
    paid: 'Achitată integral',
    partial: 'Parțial achitată',
    pending: 'În așteptare',
    cancelled: 'Anulată',
};

const paymentStatusLabelsEn: Record<PaymentStatus, string> = {
    paid: 'Paid in full',
    partial: 'Partially paid',
    pending: 'Pending',
    cancelled: 'Cancelled',
};

export const paymentMethodLabels: Record<PaymentMethod, string> = {
    cash: 'Numerar',
    bank_transfer: 'Transfer bancar',
    card: 'Card',
    other: 'Altă metodă',
};

const paymentMethodLabelsEn: Record<PaymentMethod, string> = {
    cash: 'Cash',
    bank_transfer: 'Bank transfer',
    card: 'Card',
    other: 'Other method',
};

const rentSummaryLabels: Record<PaymentSummaryStatus, string> = {
    paid: 'Chirie achitată integral',
    partial: 'Chirie parțial achitată',
    pending: 'Chirie neîncasată',
    not_configured: 'Chirie nesetată',
    unpaid: 'Chirie neîncasată',
};

const rentSummaryLabelsEn: Record<PaymentSummaryStatus, string> = {
    paid: 'Rent paid in full',
    partial: 'Rent partially paid',
    pending: 'Rent not collected',
    not_configured: 'Rent not configured',
    unpaid: 'Rent not collected',
};

const guaranteeSummaryLabels: Record<PaymentSummaryStatus, string> = {
    paid: 'Garanție achitată integral',
    partial: 'Garanție parțial achitată',
    pending: 'Garanție neîncasată',
    not_configured: 'Garanție nesetată',
    unpaid: 'Garanție neîncasată',
};

const guaranteeSummaryLabelsEn: Record<PaymentSummaryStatus, string> = {
    paid: 'Deposit paid in full',
    partial: 'Deposit partially paid',
    pending: 'Deposit not collected',
    not_configured: 'Deposit not configured',
    unpaid: 'Deposit not collected',
};

export function paymentStatusLabel(value: PaymentStatus) {
    const labels =
        currentAppLocale() === 'en'
            ? paymentStatusLabelsEn
            : paymentStatusLabels;

    return labels[value] ?? value;
}

export function paymentTypeLabel(value: PaymentType) {
    const labels =
        currentAppLocale() === 'en' ? paymentTypeLabelsEn : paymentTypeLabels;

    return labels[value] ?? value;
}

export function paymentMethodLabel(value?: PaymentMethod | null) {
    if (!value) {
        return currentAppLocale() === 'en' ? 'Not set' : 'Nesetat';
    }

    const labels =
        currentAppLocale() === 'en'
            ? paymentMethodLabelsEn
            : paymentMethodLabels;

    return labels[value] ?? value;
}

export function paymentSummaryStatusLabel(
    paymentType: PaymentType,
    statusKey: PaymentSummaryStatus,
) {
    const isEnglish = currentAppLocale() === 'en';

    if (paymentType === 'guarantee') {
        const labels = isEnglish
            ? guaranteeSummaryLabelsEn
            : guaranteeSummaryLabels;

        return labels[statusKey] ?? statusKey;
    }

    const labels = isEnglish ? rentSummaryLabelsEn : rentSummaryLabels;

    return labels[statusKey] ?? statusKey;
}

export function paymentPeriodLabel(month: number | null, year: number | null) {
    if (month === null || year === null) {
        return currentAppLocale() === 'en'
            ? 'No rent period'
            : 'Fără perioadă de chirie';
    }

    const date = new Date(Date.UTC(year, month - 1, 1));

    const label = new Intl.DateTimeFormat(currentIntlLocale(), {
        month: 'long',
        year: 'numeric',
        timeZone: 'UTC',
    }).format(date);

    return label.charAt(0).toUpperCase() + label.slice(1);
}

export function paymentPeriodLabelFromDate(value: string) {
    const [year, month] = value.split('-').map(Number);

    return paymentPeriodLabel(
        Number.isFinite(month) ? month : null,
        Number.isFinite(year) ? year : null,
    );
}
