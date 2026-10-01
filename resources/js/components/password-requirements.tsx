import { ShieldCheck } from 'lucide-react';
import { useI18n } from '@/lib/i18n';

export default function PasswordRequirements() {
    const { t } = useI18n();

    return (
        <div
            className="flex gap-2 rounded-xl border border-border/70 bg-muted/35 px-3 py-2.5 text-xs leading-5 text-muted-foreground"
            data-test="password-requirements"
        >
            <ShieldCheck className="mt-0.5 size-4 shrink-0 text-primary" aria-hidden="true" />
            <span>{t('auth.passwordRequirements')}</span>
        </div>
    );
}
