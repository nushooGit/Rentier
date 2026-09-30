import { router } from '@inertiajs/react';
import { KeyRound } from 'lucide-react';
import { destroy } from '@/actions/Laravel/Passkeys/Http/Controllers/PasskeyRegistrationController';
import Heading from '@/components/heading';
import PasskeyItem from '@/components/passkey-item';
import PasskeyRegistration from '@/components/passkey-register';
import { useI18n } from '@/lib/i18n';
import type { Passkey } from '@/types/auth';

export type Props = {
    canManagePasskeys?: boolean;
    passkeys?: Passkey[];
};

function EmptyState() {
    const { t } = useI18n();

    return (
        <div className="p-8 text-center">
            <div className="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-primary/10 text-primary">
                <KeyRound className="h-7 w-7" />
            </div>
            <p className="font-medium">{t('settings.passkeys.emptyTitle')}</p>
            <p className="mt-1 text-sm text-muted-foreground">
                {t('settings.passkeys.emptyDescription')}
            </p>
        </div>
    );
}

export default function ManagePasskeys(props: Props) {
    const passkeys = props.passkeys ?? [];
    const { t } = useI18n();

    const handleDelete = (id: number, onError: () => void) => {
        router.delete(destroy.url(id), {
            preserveScroll: true,
            onError,
        });
    };

    const handleRegisterSuccess = () => {
        router.reload();
    };

    if (!(props.canManagePasskeys ?? false)) {
        return null;
    }

    return (
        <div className="space-y-6">
            <Heading
                variant="small"
                title={t('settings.passkeys.title')}
                description={t('settings.passkeys.description')}
            />

            <div className="overflow-hidden rounded-2xl border border-border/70 bg-card/80 shadow-sm">
                {passkeys.length > 0 ? (
                    passkeys.map((passkey) => (
                        <PasskeyItem
                            key={passkey.id}
                            passkey={passkey}
                            onDelete={handleDelete}
                        />
                    ))
                ) : (
                    <EmptyState />
                )}
            </div>

            <PasskeyRegistration onSuccess={handleRegisterSuccess} />
        </div>
    );
}
