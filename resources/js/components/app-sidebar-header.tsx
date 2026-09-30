import { usePage } from '@inertiajs/react';
import { Breadcrumbs } from '@/components/breadcrumbs';
import { LocaleSwitcher } from '@/components/locale-switcher';
import { ThemeToggle } from '@/components/theme-toggle';
import { SidebarTrigger } from '@/components/ui/sidebar';
import { useI18n } from '@/lib/i18n';
import type { BreadcrumbItem as BreadcrumbItemType } from '@/types';

export function AppSidebarHeader({
    breadcrumbs = [],
}: {
    breadcrumbs?: BreadcrumbItemType[];
}) {
    const { currentTeam } = usePage().props;
    const { t } = useI18n();

    return (
        <header className="sticky top-0 z-30 flex h-15 shrink-0 items-center justify-between gap-3 border-b border-border/70 bg-background/82 px-3 backdrop-blur-xl sm:px-5">
            <div className="flex min-w-0 items-center gap-2.5">
                <SidebarTrigger className="-ml-1 size-8 rounded-xl border border-border/60 bg-card/70 shadow-sm" />
                <div className="min-w-0">
                    <Breadcrumbs breadcrumbs={breadcrumbs} />
                    <p className="mt-0.5 hidden truncate text-[11px] text-muted-foreground sm:block">
                        {currentTeam?.name ?? t('header.workspaceFallback')}
                    </p>
                </div>
            </div>
            <div className="flex items-center gap-1.5 sm:gap-2">
                <span className="hidden rounded-full border border-emerald-200/70 bg-emerald-50/80 px-3 py-1 text-[11px] font-semibold text-emerald-700 shadow-sm dark:border-emerald-400/20 dark:bg-emerald-400/10 dark:text-emerald-300 md:inline-flex">
                    {t('header.privateBeta')}
                </span>
                <LocaleSwitcher />
                <ThemeToggle />
            </div>
        </header>
    );
}
