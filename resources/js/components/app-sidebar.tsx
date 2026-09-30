import { Link, usePage } from '@inertiajs/react';
import {
    Building2,
    FileText,
    FolderOpen,
    LayoutDashboard,
    ReceiptText,
    WalletCards,
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
import { dashboard } from '@/routes';
import { index as documentsIndex } from '@/routes/documents';
import { index as expensesIndex } from '@/routes/expenses';
import { index as leasesIndex } from '@/routes/leases';
import { index as paymentsIndex } from '@/routes/payments';
import { index as propertiesIndex } from '@/routes/properties';
import type { NavItem } from '@/types';

export function AppSidebar() {
    const page = usePage();
    const dashboardUrl = page.props.currentTeam
        ? dashboard(page.props.currentTeam.slug)
        : '/';

    const mainNavItems: NavItem[] = [
        {
            title: 'Dashboard',
            href: dashboardUrl,
            icon: LayoutDashboard,
        },
        {
            title: 'Proprietăți',
            href: page.props.currentTeam
                ? propertiesIndex(page.props.currentTeam.slug)
                : '/',
            icon: Building2,
        },
        {
            title: 'Contracte',
            href: page.props.currentTeam
                ? leasesIndex(page.props.currentTeam.slug)
                : '/',
            icon: FileText,
        },
        {
            title: 'Plăți',
            href: page.props.currentTeam
                ? paymentsIndex(page.props.currentTeam.slug)
                : '/',
            icon: WalletCards,
        },
        {
            title: 'Cheltuieli',
            href: page.props.currentTeam
                ? expensesIndex(page.props.currentTeam.slug)
                : '/',
            icon: ReceiptText,
        },
        {
            title: 'Documente',
            href: page.props.currentTeam
                ? documentsIndex(page.props.currentTeam.slug)
                : '/',
            icon: FolderOpen,
        },
    ];


    return (
        <Sidebar collapsible="icon" variant="inset" className="border-r border-white/5">
            <SidebarHeader className="gap-3 p-3">
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
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

            <SidebarContent className="px-1">
                <NavMain items={mainNavItems} />
            </SidebarContent>

            <SidebarFooter className="border-t border-white/8 p-3">
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
