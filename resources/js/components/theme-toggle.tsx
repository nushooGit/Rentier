import { Moon, Sun } from 'lucide-react';
import { useAppearance } from '@/hooks/use-appearance';
import { cn } from '@/lib/utils';

export function ThemeToggle({ className = '' }: { className?: string }) {
    const { resolvedAppearance, updateAppearance } = useAppearance();
    const isDark = resolvedAppearance === 'dark';

    return (
        <button
            type="button"
            onClick={() => updateAppearance(isDark ? 'light' : 'dark')}
            className={cn(
                'inline-flex size-9 items-center justify-center rounded-xl border border-border/70 bg-background/80 text-muted-foreground shadow-sm transition hover:bg-accent hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring',
                className,
            )}
            aria-label={
                isDark
                    ? 'Activează modul luminos'
                    : 'Activează modul întunecat'
            }
            title={
                isDark
                    ? 'Activează modul luminos'
                    : 'Activează modul întunecat'
            }
            data-test="theme-toggle"
        >
            {isDark ? (
                <Sun className="size-4" aria-hidden="true" />
            ) : (
                <Moon className="size-4" aria-hidden="true" />
            )}
        </button>
    );
}
