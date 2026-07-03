import type { LeaseStatus } from '@/types';

export const leaseStatusLabels: Record<LeaseStatus, string> = {
    upcoming: 'Viitor',
    active: 'Activ',
    ended: 'Închis',
    cancelled: 'Anulat',
};

export function leaseStatusLabel(value: LeaseStatus) {
    return leaseStatusLabels[value] ?? value;
}
