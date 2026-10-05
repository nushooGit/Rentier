import { Link, usePage } from '@inertiajs/react';
import {
    Building2,
    CalendarDays,
    FileSpreadsheet,
    FileText,
    FolderOpen,
    LayoutDashboard,
    ReceiptText,
    WalletCards,
    Zap,
} from 'lucide-react';
import AppLogo from '@/components/app-logo';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import { TeamSwitcher } from '@/components/team-switcher';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { useI18n } from '@/lib/i18n';
import { dashboard } from '@/routes';
import { index as calendarIndex } from '@/routes/calendar';
import { index as documentsIndex } from '@/routes/documents';
import { index as expensesIndex } from '@/routes/expenses';
import { index as exportsIndex } from '@/routes/exports';
import { index as leasesIndex } from '@/routes/leases';
import { index as paymentsIndex } from '@/routes/payments';
import { index as propertiesIndex } from '@/routes/properties';
import { index as utilitiesIndex } from '@/routes/utilities';
import type { NavItem } from '@/types';

export function AppSidebar() {
    const page = usePage();
    const { t } = useI18n();
    const dashboardUrl = page.props.currentTeam
        ? dashboard(page.props.currentTeam.slug)
        : '/';

    const mainNavItems: NavItem[] = [
        { title: t('nav.dashboard'), href: dashboardUrl, icon: LayoutDashboard },
        {
            title: t('nav.calendar'),
            href: page.props.currentTeam ? calendarIndex(page.props.currentTeam.slug) : '/',
            icon: CalendarDays,
        },
        {
            title: t('nav.properties'),
            href: page.props.currentTeam ? propertiesIndex(page.props.currentTeam.slug) : '/',
            icon: Building2,
        },
        {
            title: t('nav.leases'),
            href: page.props.currentTeam ? leasesIndex(page.props.currentTeam.slug) : '/',
            icon: FileText,
        },
        {
            title: t('nav.payments'),
            href: page.props.currentTeam ? paymentsIndex(page.props.currentTeam.slug) : '/',
            icon: WalletCards,
        },
        {
            title: t('nav.expenses'),
            href: page.props.currentTeam ? expensesIndex(page.props.currentTeam.slug) : '/',
            icon: ReceiptText,
        },
        {
            title: t('nav.utilities'),
            href: page.props.currentTeam ? utilitiesIndex(page.props.currentTeam.slug) : '/',
            icon: Zap,
        },
        {
            title: t('nav.documents'),
            href: page.props.currentTeam ? documentsIndex(page.props.currentTeam.slug) : '/',
            icon: FolderOpen,
        },
        {
            title: t('nav.exports'),
            href: page.props.currentTeam ? exportsIndex(page.props.currentTeam.slug) : '/',
            icon: FileSpreadsheet,
        },
    ];

    return (
        <Sidebar collapsible="icon" variant="inset" className="border-r border-white/5 shadow-2xl shadow-slate-950/10">
            <SidebarHeader className="gap-3 px-3 pt-4 pb-3">
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild className="rounded-2xl hover:bg-white/[0.06]">
                            <Link href={dashboardUrl} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <TeamSwitcher />
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>
            <SidebarContent className="px-1.5 pt-2">
                <NavMain items={mainNavItems} />
            </SidebarContent>
            <SidebarFooter className="border-t border-white/8 p-3">
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
