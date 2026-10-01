import { Check } from 'lucide-react';
import { useI18n } from '@/lib/i18n';
import { cn } from '@/lib/utils';

export default function PasswordRequirements({
    className,
}: {
    className?: string;
}) {
    const { t } = useI18n();

    const requirements = [
        t('password.requirements.length'),
        t('password.requirements.mixed'),
        t('password.requirements.number'),
        t('password.requirements.symbol'),
    ];

    return (
        <div
            className={cn(
                'rounded-xl border border-border/70 bg-muted/35 p-3 text-xs text-muted-foreground',
                className,
            )}
            role="note"
            data-test="password-requirements"
        >
            <p className="font-medium text-foreground">
                {t('password.requirements.title')}
            </p>
            <ul className="mt-2 grid gap-1.5 sm:grid-cols-2">
                {requirements.map((requirement) => (
                    <li key={requirement} className="flex items-start gap-2">
                        <Check
                            className="mt-0.5 size-3.5 shrink-0 text-emerald-600 dark:text-emerald-400"
                            aria-hidden="true"
                        />
                        <span>{requirement}</span>
                    </li>
                ))}
            </ul>
            <p className="mt-2">{t('password.requirements.uncompromised')}</p>
        </div>
    );
}
