import { currentAppLocale } from '@/lib/locale';
import type { PaymentMethod, PaymentStatus, PaymentType } from '@/types';

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

export function paymentStatusLabel(value: PaymentStatus) {
    const labels = currentAppLocale() === 'en' ? paymentStatusLabelsEn : paymentStatusLabels;
    return labels[value] ?? value;
}

export function paymentTypeLabel(value: PaymentType) {
    const labels = currentAppLocale() === 'en' ? paymentTypeLabelsEn : paymentTypeLabels;
    return labels[value] ?? value;
}

export function paymentMethodLabel(value?: PaymentMethod | null) {
    if (!value) {
        return currentAppLocale() === 'en' ? 'Not set' : 'Nesetat';
    }

    const labels = currentAppLocale() === 'en' ? paymentMethodLabelsEn : paymentMethodLabels;
    return labels[value] ?? value;
}
