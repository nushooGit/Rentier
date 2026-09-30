import { Check, Languages } from 'lucide-react';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { useI18n } from '@/lib/i18n';
import { persistAppLocale, type AppLocale } from '@/lib/locale';
import { cn } from '@/lib/utils';

export function LocaleSwitcher({ className = '' }: { className?: string }) {
    const { locale, t } = useI18n();

    const changeLocale = (nextLocale: AppLocale) => {
        if (nextLocale === locale) return;
        persistAppLocale(nextLocale);
        window.location.reload();
    };

    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <button
                    type="button"
                    className={cn(
                        'inline-flex h-9 items-center gap-2 rounded-xl border border-border/70 bg-background/80 px-2.5 text-xs font-semibold text-muted-foreground shadow-sm transition hover:bg-accent hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring',
                        className,
                    )}
                    aria-label={t('language.label')}
                    title={t('language.label')}
                    data-test="locale-switcher"
                >
                    <Languages className="size-4" aria-hidden="true" />
                    <span>{locale.toUpperCase()}</span>
                </button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" className="min-w-40">
                <DropdownMenuLabel>{t('language.label')}</DropdownMenuLabel>
                <DropdownMenuItem className="cursor-pointer" onSelect={() => changeLocale('ro')}>
                    {t('language.romanian')}
                    {locale === 'ro' ? <Check className="ml-auto size-4" /> : null}
                </DropdownMenuItem>
                <DropdownMenuItem className="cursor-pointer" onSelect={() => changeLocale('en')}>
                    {t('language.english')}
                    {locale === 'en' ? <Check className="ml-auto size-4" /> : null}
                </DropdownMenuItem>
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
