import type { PaymentMethod, PaymentStatus } from '@/types';

export const paymentStatusLabels: Record<PaymentStatus, string> = {
    paid: 'Achitată integral',
    partial: 'Parțial achitată',
    pending: 'În așteptare',
    cancelled: 'Anulată',
};

export const paymentMethodLabels: Record<PaymentMethod, string> = {
    cash: 'Numerar',
    bank_transfer: 'Transfer bancar',
    card: 'Card',
    other: 'Altă metodă',
};

export function paymentStatusLabel(value: PaymentStatus) {
    return paymentStatusLabels[value] ?? value;
}

export function paymentMethodLabel(value?: PaymentMethod | null) {
    return value ? (paymentMethodLabels[value] ?? value) : 'Nesetat';
}
