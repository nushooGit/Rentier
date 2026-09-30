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
import { dashboard } from '@/routes';
import { index as documentsIndex } from '@/routes/documents';
import { index as expensesIndex } from '@/routes/expenses';
import { index as leasesIndex } from '@/routes/leases';
import { index as paymentsIndex } from '@/routes/payments';
import { index as propertiesIndex } from '@/routes/properties';

export function MobileBottomNav() {
    const { currentTeam } = usePage().props;
    const { isCurrentUrl } = useCurrentUrl();

    if (!currentTeam) {
        return null;
    }

    const items = [
        {
            label: 'Acasă',
            href: dashboard(currentTeam.slug),
            icon: LayoutDashboard,
        },
        {
            label: 'Proprietăți',
            href: propertiesIndex(currentTeam.slug),
            icon: Building2,
        },
        {
            label: 'Contracte',
            href: leasesIndex(currentTeam.slug),
            icon: FileText,
        },
        {
            label: 'Plăți',
            href: paymentsIndex(currentTeam.slug),
            icon: WalletCards,
        },
        {
            label: 'Cheltuieli',
            href: expensesIndex(currentTeam.slug),
            icon: ReceiptText,
        },
        {
            label: 'Documente',
            href: documentsIndex(currentTeam.slug),
            icon: FolderOpen,
        },
    ];

    return (
        <nav
            className="fixed inset-x-0 bottom-0 z-40 border-t border-slate-200/80 bg-white/95 px-1.5 pb-[max(env(safe-area-inset-bottom),0.35rem)] pt-1.5 shadow-[0_-10px_30px_rgba(15,23,42,0.08)] backdrop-blur md:hidden dark:border-white/10 dark:bg-[#07111f]/95"
            aria-label="Navigație principală"
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
                                    ? 'flex min-h-14 flex-col items-center justify-center gap-1 rounded-xl text-[9px] font-semibold sm:text-[10px] text-emerald-700 dark:text-emerald-300'
                                    : 'flex min-h-14 flex-col items-center justify-center gap-1 rounded-xl text-[9px] font-medium sm:text-[10px] text-slate-500 transition hover:text-slate-900 dark:text-slate-400 dark:hover:text-white'
                            }
                        >
                            <span
                                className={
                                    active
                                        ? 'flex size-8 items-center justify-center rounded-xl bg-emerald-100 dark:bg-emerald-400/10'
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
