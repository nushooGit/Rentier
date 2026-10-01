import { InfoIcon } from 'lucide-react';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { useI18n } from '@/lib/i18n';
import type { TeamInvitationContext } from '@/types';

type Props = {
    invitation: TeamInvitationContext;
    action: 'Log in' | 'Register';
};

export default function TeamInvitationAlert({ invitation, action }: Props) {
    const { t } = useI18n();

    return (
        <Alert
            data-test="team-invitation-alert"
            className="border-blue-200 bg-blue-50 text-blue-900 dark:border-blue-900/50 dark:bg-blue-950/50 dark:text-blue-100 [&>svg]:text-blue-600 dark:[&>svg]:text-blue-400"
        >
            <InfoIcon />
            <AlertDescription className="text-blue-900 dark:text-blue-100">
                {t(
                    action === 'Log in'
                        ? 'auth.invitation.login'
                        : 'auth.invitation.register',
                    { team: invitation.teamName },
                )}
            </AlertDescription>
        </Alert>
    );
}
