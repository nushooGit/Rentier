import { Form } from '@inertiajs/react';
import type { PropsWithChildren } from 'react';
import { useState } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useI18n } from '@/lib/i18n';
import { store } from '@/routes/teams';

export default function CreateTeamModal({ children }: PropsWithChildren) {
    const { t } = useI18n();
    const [open, setOpen] = useState(false);

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>{children}</DialogTrigger>
            <DialogContent>
                <Form
                    key={String(open)}
                    {...store.form()}
                    className="space-y-6"
                    onSuccess={() => setOpen(false)}
                >
                    {({ errors, processing }) => (
                        <>
                            <DialogHeader>
                                <DialogTitle>{t('workspaces.create.title')}</DialogTitle>
                                <DialogDescription>
                                    {t('workspaces.create.description')}
                                </DialogDescription>
                            </DialogHeader>

                            <div className="grid gap-2">
                                <Label htmlFor="name">
                                    {t('workspaces.create.name')}
                                </Label>
                                <Input
                                    id="name"
                                    name="name"
                                    data-test="create-team-name"
                                    placeholder={t('workspaces.create.placeholder')}
                                    required
                                />
                                <InputError message={errors.name} />
                            </div>

                            <DialogFooter className="gap-2">
                                <DialogClose asChild>
                                    <Button variant="secondary">
                                        {t('common.cancel')}
                                    </Button>
                                </DialogClose>

                                <Button
                                    type="submit"
                                    data-test="create-team-submit"
                                    disabled={processing}
                                >
                                    {t('workspaces.create.submit')}
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}
