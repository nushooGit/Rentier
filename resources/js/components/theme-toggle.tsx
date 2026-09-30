import { Moon, Sun } from 'lucide-react';
import { useAppearance } from '@/hooks/use-appearance';
import { useI18n } from '@/lib/i18n';
import { cn } from '@/lib/utils';

export function ThemeToggle({ className = '' }: { className?: string }) {
    const { resolvedAppearance, updateAppearance } = useAppearance();
    const { t } = useI18n();
    const isDark = resolvedAppearance === 'dark';
    const label = isDark ? t('theme.enableLight') : t('theme.enableDark');

    return (
        <button
            type="button"
            onClick={() => updateAppearance(isDark ? 'light' : 'dark')}
            className={cn(
                'inline-flex size-9 items-center justify-center rounded-xl border border-border/70 bg-background/80 text-muted-foreground shadow-sm transition hover:bg-accent hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring',
                className,
            )}
            aria-label={label}
            title={label}
            data-test="theme-toggle"
        >
            {isDark ? <Sun className="size-4" aria-hidden="true" /> : <Moon className="size-4" aria-hidden="true" />}
        </button>
    );
}
