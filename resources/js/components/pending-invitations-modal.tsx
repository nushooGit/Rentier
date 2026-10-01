import { router } from '@inertiajs/react';
import { useState } from 'react';
import TeamInvitationController from '@/actions/App/Http/Controllers/Teams/TeamInvitationController';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { useI18n } from '@/lib/i18n';
import type { DashboardInvitation } from '@/types';

type Props = {
    invitations: DashboardInvitation[];
    open: boolean;
    onOpenChange: (open: boolean) => void;
};

export default function PendingInvitationsModal({
    invitations,
    open,
    onOpenChange,
}: Props) {
    const [processingCode, setProcessingCode] = useState<string | null>(null);
    const { t } = useI18n();

    const acceptInvitation = (invitation: DashboardInvitation) => {
        router.visit(TeamInvitationController.accept(invitation), {
            onStart: () => setProcessingCode(invitation.code),
            onFinish: () => setProcessingCode(null),
        });
    };

    const declineInvitation = (invitation: DashboardInvitation) => {
        router.visit(TeamInvitationController.decline(invitation), {
            onStart: () => setProcessingCode(invitation.code),
            onFinish: () => setProcessingCode(null),
            onSuccess: () => {
                if (invitations.length === 1) {
                    onOpenChange(false);
                }
            },
        });
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent data-test="pending-invitations-modal">
                <DialogHeader>
                    <DialogTitle>{t('team.pending.title')}</DialogTitle>
                    <DialogDescription>
                        {t('team.pending.description')}
                    </DialogDescription>
                </DialogHeader>

                <div className="grid gap-4">
                    {invitations.map((invitation) => (
                        <div
                            key={invitation.code}
                            data-test="pending-invitation-row"
                            className="rounded-2xl border border-border/70 bg-card/70 p-4"
                        >
                            <div className="space-y-1">
                                <p className="font-medium">
                                    {invitation.team.name}
                                </p>
                                <p className="text-sm text-muted-foreground">
                                    {t('team.pending.invitedBy', {
                                        name: invitation.inviterName,
                                    })}
                                </p>
                            </div>

                            <div className="mt-4 flex justify-end gap-2">
                                <Button
                                    variant="secondary"
                                    data-test="pending-invitation-decline"
                                    disabled={
                                        processingCode === invitation.code
                                    }
                                    onClick={() =>
                                        declineInvitation(invitation)
                                    }
                                >
                                    {t('team.pending.decline')}
                                </Button>

                                <Button
                                    data-test="pending-invitation-accept"
                                    disabled={
                                        processingCode === invitation.code
                                    }
                                    onClick={() => acceptInvitation(invitation)}
                                >
                                    {t('team.pending.accept')}
                                </Button>
                            </div>
                        </div>
                    ))}
                </div>
            </DialogContent>
        </Dialog>
    );
}
