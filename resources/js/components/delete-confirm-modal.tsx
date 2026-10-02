import { router } from "@inertiajs/react";
import { useState } from "react";
import { Button } from "@/components/ui/button";
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from "@/components/ui/dialog";
import { useTranslation } from "@/hooks/use-translation";

import Trans from '@/components/trans';
type Props = {
    title: string;
    name: string | null;
    url: string | null;
    open: boolean;
    onOpenChange: (open: boolean) => void;
};

export default function DeleteConfirmModal({
    title,
    name,
    url,
    open,
    onOpenChange,
}: Props) {
    const { t } = useTranslation();
    const [processing, setProcessing] = useState(false);

    const confirmDelete = () => {
        if (!url) {
            return;
        }

        router.delete(url, {
            preserveScroll: true,
            onStart: () => setProcessing(true),
            onFinish: () => setProcessing(false),
            onSuccess: () => onOpenChange(false),
        });
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>{t(title)}</DialogTitle>
                    <DialogDescription>
                        <Trans
                            text="Are you sure you want to delete :name? This action cannot be undone."
                            values={{ name: <strong>{name}</strong> }}
                        />
                    </DialogDescription>
                </DialogHeader>

                <DialogFooter className="gap-2">
                    <DialogClose asChild>
                        <Button variant="secondary">{t("Cancel")}</Button>
                    </DialogClose>

                    <Button
                        variant="destructive"
                        data-test="delete-confirm"
                        disabled={processing}
                        onClick={confirmDelete}
                    >
                        {t("Delete")}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
