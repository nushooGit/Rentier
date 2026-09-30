import { Link, usePage } from '@inertiajs/react';
import {
    Building2,
    FileText,
    FolderOpen,
    LayoutDashboard,
    ReceiptText,
    WalletCards,
} from 'lucide-react';
import { useCurrentUrl } from '@/hooks/use-current-url';
import { useI18n } from '@/lib/i18n';
import { dashboard } from '@/routes';
import { index as documentsIndex } from '@/routes/documents';
import { index as expensesIndex } from '@/routes/expenses';
import { index as leasesIndex } from '@/routes/leases';
import { index as paymentsIndex } from '@/routes/payments';
import { index as propertiesIndex } from '@/routes/properties';

export function MobileBottomNav() {
    const { currentTeam } = usePage().props;
    const { isCurrentUrl } = useCurrentUrl();
    const { t } = useI18n();

    if (!currentTeam) return null;

    const items = [
        { label: t('nav.home'), href: dashboard(currentTeam.slug), icon: LayoutDashboard },
        { label: t('nav.properties'), href: propertiesIndex(currentTeam.slug), icon: Building2 },
        { label: t('nav.leases'), href: leasesIndex(currentTeam.slug), icon: FileText },
        { label: t('nav.payments'), href: paymentsIndex(currentTeam.slug), icon: WalletCards },
        { label: t('nav.expenses'), href: expensesIndex(currentTeam.slug), icon: ReceiptText },
        { label: t('nav.documents'), href: documentsIndex(currentTeam.slug), icon: FolderOpen },
    ];

    return (
        <nav
            className="fixed inset-x-0 bottom-0 z-40 border-t border-border/80 bg-background/92 px-1.5 pb-[max(env(safe-area-inset-bottom),0.35rem)] pt-1.5 shadow-[0_-14px_36px_rgba(15,23,42,0.10)] backdrop-blur-xl md:hidden"
            aria-label={t('mobile.primaryNavigation')}
        >
            <div className="mx-auto grid max-w-lg grid-cols-6">
                {items.map(({ label, href, icon: Icon }) => {
                    const active = isCurrentUrl(href);

                    return (
                        <Link
                            key={label}
                            href={href}
                            className={
                                active
                                    ? 'flex min-h-14 flex-col items-center justify-center gap-1 rounded-xl text-[9px] font-semibold text-primary sm:text-[10px]'
                                    : 'flex min-h-14 flex-col items-center justify-center gap-1 rounded-xl text-[9px] font-medium text-muted-foreground transition hover:text-foreground sm:text-[10px]'
                            }
                        >
                            <span
                                className={
                                    active
                                        ? 'flex size-8 items-center justify-center rounded-xl bg-primary/10 ring-1 ring-primary/15'
                                        : 'flex size-8 items-center justify-center'
                                }
                            >
                                <Icon className="size-4" aria-hidden="true" />
                            </span>
                            <span className="max-w-full truncate">{label}</span>
                        </Link>
                    );
                })}
            </div>
        </nav>
    );
}
