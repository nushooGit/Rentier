import type { LeaseStatus } from '@/types';

export const leaseStatusLabels: Record<LeaseStatus, string> = {
    upcoming: 'Viitor',
    active: 'Activ',
    ended: 'Încheiat',
    cancelled: 'Anulat',
};

export function leaseStatusLabel(value: LeaseStatus) {
    return leaseStatusLabels[value] ?? value;
}
