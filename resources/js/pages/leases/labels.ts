import { currentAppLocale } from '@/lib/locale';
import type { LeaseStatus } from '@/types';

export const leaseStatusLabels: Record<LeaseStatus, string> = {
    upcoming: 'Viitor',
    active: 'Activ',
    ended: 'Închis',
    cancelled: 'Anulat',
};

const leaseStatusLabelsEn: Record<LeaseStatus, string> = {
    upcoming: 'Upcoming',
    active: 'Active',
    ended: 'Ended',
    cancelled: 'Cancelled',
};

export function leaseStatusLabel(value: LeaseStatus) {
    const labels = currentAppLocale() === 'en' ? leaseStatusLabelsEn : leaseStatusLabels;
    return labels[value] ?? value;
}
