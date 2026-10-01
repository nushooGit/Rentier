import { translateKey } from '@/lib/i18n';
import type { TeamRole } from '@/types';

export function workspaceRoleLabel(role: TeamRole) {
    switch (role) {
        case 'owner':
            return translateKey('workspaces.role.owner');
        case 'admin':
            return translateKey('workspaces.role.admin');
        case 'member':
            return translateKey('workspaces.role.member');
        default:
            return role;
    }
}
