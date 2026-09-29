import { usePage } from '@inertiajs/react';
import { Breadcrumbs } from '@/components/breadcrumbs';
import { SidebarTrigger } from '@/components/ui/sidebar';
import type { BreadcrumbItem as BreadcrumbItemType } from '@/types';

export function AppSidebarHeader({
    breadcrumbs = [],
}: {
    breadcrumbs?: BreadcrumbItemType[];
}) {
    const { currentTeam } = usePage().props;

    return (
        <header className="flex h-16 shrink-0 items-center justify-between gap-3 border-b border-border/70 bg-background/95 px-4 backdrop-blur sm:px-6">
            <div className="flex min-w-0 items-center gap-3">
                <SidebarTrigger className="-ml-1 size-8 rounded-lg" />
                <div className="min-w-0">
                    <Breadcrumbs breadcrumbs={breadcrumbs} />
                    <p className="mt-0.5 hidden truncate text-xs text-muted-foreground sm:block">
                        {currentTeam?.name ?? 'Workspace Rentier'}
                    </p>
                </div>
            </div>
            <span className="hidden rounded-full border border-emerald-200/70 bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700 dark:border-emerald-400/20 dark:bg-emerald-400/10 dark:text-emerald-300 sm:inline-flex">
                Beta privată
            </span>
        </header>
    );
}
