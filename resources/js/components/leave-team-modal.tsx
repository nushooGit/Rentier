import { router } from '@inertiajs/react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { useI18n } from '@/lib/i18n';
import { leave as leaveTeamAction } from '@/routes/teams';
import type { Team } from '@/types';

type Props = {
    team: Team | null;
    open: boolean;
    onOpenChange: (open: boolean) => void;
};

export default function LeaveTeamModal({ team, open, onOpenChange }: Props) {
    const [processing, setProcessing] = useState(false);
    const { t } = useI18n();

    const leaveTeam = () => {
        if (!team) {
            return;
        }

        router.visit(leaveTeamAction(team.slug), {
            onStart: () => setProcessing(true),
            onFinish: () => setProcessing(false),
            onSuccess: () => onOpenChange(false),
        });
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>{t('team.leave.title')}</DialogTitle>
                    <DialogDescription>
                        {t('team.leave.description', {
                            name: team?.name ?? '',
                        })}
                    </DialogDescription>
                </DialogHeader>

                <DialogFooter className="gap-2">
                    <DialogClose asChild>
                        <Button variant="secondary">{t('common.cancel')}</Button>
                    </DialogClose>

                    <Button
                        variant="destructive"
                        data-test="leave-team-confirm"
                        disabled={processing}
                        onClick={leaveTeam}
                    >
                        {t('team.leave.submit')}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
