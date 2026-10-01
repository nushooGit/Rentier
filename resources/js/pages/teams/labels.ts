import { translateKey } from '@/lib/i18n';
import type { TeamRole } from '@/types';

export function teamRoleLabel(role: TeamRole) {
    switch (role) {
        case 'owner':
            return translateKey('team.role.owner');
        case 'admin':
            return translateKey('team.role.admin');
        case 'member':
            return translateKey('team.role.member');
        default:
            return role;
    }
}
