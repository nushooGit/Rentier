import { Link } from '@inertiajs/react';
import {
    SidebarGroup,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { useCurrentUrl } from '@/hooks/use-current-url';
import { useI18n } from '@/lib/i18n';
import type { NavItem } from '@/types';

export function NavMain({ items = [] }: { items: NavItem[] }) {
    const { isCurrentUrl } = useCurrentUrl();
    const { t } = useI18n();

    return (
        <SidebarGroup className="px-2 py-0">
            <SidebarGroupLabel className="px-2 text-[10px] font-semibold uppercase tracking-[0.18em] text-sidebar-foreground/40">
                {t('nav.main')}
            </SidebarGroupLabel>
            <SidebarMenu className="mt-1 gap-1">
                {items.map((item) => (
                    <SidebarMenuItem key={item.title}>
                        <SidebarMenuButton
                            asChild
                            isActive={isCurrentUrl(item.href)}
                            tooltip={{ children: item.title }}
                            className="group/nav h-10 rounded-xl px-3 text-sidebar-foreground/68 hover:bg-white/[0.06] hover:text-sidebar-foreground data-[active=true]:bg-emerald-300/[0.11] data-[active=true]:font-semibold data-[active=true]:text-emerald-200"
                        >
                            <Link href={item.href}>
                                {item.icon && <item.icon className="transition-transform group-hover/nav:scale-105" />}
                                <span>{item.title}</span>
                                <span className="ml-auto hidden size-1.5 rounded-full bg-emerald-300 group-data-[active=true]/nav:block" />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                ))}
            </SidebarMenu>
        </SidebarGroup>
    );
}
