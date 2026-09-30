import { Link } from '@inertiajs/react';
import type { PropsWithChildren } from 'react';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { useCurrentUrl } from '@/hooks/use-current-url';
import { useI18n } from '@/lib/i18n';
import { cn, toUrl } from '@/lib/utils';
import { edit as editAppearance } from '@/routes/appearance';
import { edit } from '@/routes/profile';
import { edit as editSecurity } from '@/routes/security';
import { index as teams } from '@/routes/teams';
import type { NavItem } from '@/types';

export default function SettingsLayout({ children }: PropsWithChildren) {
    const { isCurrentOrParentUrl } = useCurrentUrl();
    const { t } = useI18n();
    const sidebarNavItems: NavItem[] = [
        { title: t('settings.nav.profile'), href: edit(), icon: null },
        { title: t('settings.nav.security'), href: editSecurity(), icon: null },
        { title: t('settings.nav.workspaces'), href: teams(), icon: null },
        { title: t('settings.nav.appearance'), href: editAppearance(), icon: null },
    ];

    return (
        <div className="mx-auto w-full max-w-[1180px] px-4 py-6 sm:px-6 lg:py-8">
            <Heading
                title={t('settings.title')}
                description={t('settings.description')}
            />

            <div className="flex flex-col gap-6 lg:flex-row lg:gap-10">
                <aside className="w-full lg:w-56">
                    <nav
                        className="flex gap-1 overflow-x-auto rounded-2xl border border-border/70 bg-card/70 p-2 shadow-sm lg:flex-col lg:overflow-visible"
                        aria-label={t('settings.title')}
                    >
                        {sidebarNavItems.map((item, index) => (
                            <Button
                                key={`${toUrl(item.href)}-${index}`}
                                size="sm"
                                variant="ghost"
                                asChild
                                className={cn('shrink-0 justify-start rounded-xl lg:w-full', {
                                    'bg-primary/10 text-primary': isCurrentOrParentUrl(item.href),
                                })}
                            >
                                <Link href={item.href}>
                                    {item.icon && (
                                        <item.icon className="h-4 w-4" />
                                    )}
                                    {item.title}
                                </Link>
                            </Button>
                        ))}
                    </nav>
                </aside>

                <Separator className="lg:hidden" />

                <div className="min-w-0 flex-1">
                    <section className="max-w-2xl space-y-10">
                        {children}
                    </section>
                </div>
            </div>
        </div>
    );
}
