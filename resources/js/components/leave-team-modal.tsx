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
import { leave as leaveTeamAction } from '@/routes/teams';
import type { Team } from '@/types';
import { useTranslation } from '@/hooks/use-translation';

import Trans from '@/components/trans';
type Props = {
    team: Team | null;
    open: boolean;
    onOpenChange: (open: boolean) => void;
};

export default function LeaveTeamModal({ team, open, onOpenChange }: Props) {
    const { t } = useTranslation();
    const [processing, setProcessing] = useState(false);

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
                    <DialogTitle>{t("Leave team")}</DialogTitle>
                    <DialogDescription>
                        <Trans
                            text="Are you sure you want to leave :name?"
                            values={{ name: <strong>{team?.name}</strong> }}
                        />
                    </DialogDescription>
                </DialogHeader>

                <DialogFooter className="gap-2">
                    <DialogClose asChild>
                        <Button variant="secondary">{t("Cancel")}</Button>
                    </DialogClose>

                    <Button
                        variant="destructive"
                        data-test="leave-team-confirm"
                        disabled={processing}
                        onClick={leaveTeam}
                    >
                        {t("Leave team")}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
