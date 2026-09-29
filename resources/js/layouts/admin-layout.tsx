import { Link, router, usePage } from '@inertiajs/react';
import { Building2, LayoutDashboard, LogOut, ShieldCheck, Users } from 'lucide-react';
import { ThemeToggle } from '@/components/theme-toggle';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';

type Props = {
    children: React.ReactNode;
    title: string;
    description?: string;
};

const navItems = [
    { title: 'Prezentare', href: '/', icon: LayoutDashboard },
    { title: 'Utilizatori', href: '/users', icon: Users },
    { title: 'Workspace-uri', href: '/workspaces', icon: Building2 },
];

export default function AdminLayout({ children, title, description }: Props) {
    const page = usePage();
    const user = page.props.auth.user;

    return (
        <div className="min-h-screen bg-background text-foreground">
            <header className="border-b border-border/70 bg-card/90 backdrop-blur">
                <div className="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-3 sm:px-6">
                    <Link href="/" className="flex min-w-0 items-center gap-3">
                        <span className="flex size-9 shrink-0 items-center justify-center rounded-xl bg-slate-950 text-emerald-300 dark:bg-emerald-300 dark:text-slate-950">
                            <ShieldCheck className="size-5" />
                        </span>
                        <div className="min-w-0">
                            <p className="truncate font-semibold">Rentier Admin</p>
                            <p className="truncate text-xs text-muted-foreground">
                                Administrare internă
                            </p>
                        </div>
                    </Link>

                    <div className="flex items-center gap-2">
                        <ThemeToggle />
                        <div className="hidden text-right sm:block">
                            <p className="max-w-48 truncate text-sm font-medium">{user.name}</p>
                            <p className="max-w-48 truncate text-xs text-muted-foreground">{user.email}</p>
                        </div>
                        <Button
                            type="button"
                            variant="ghost"
                            size="icon"
                            aria-label="Deconectare"
                            onClick={() => router.post('/logout')}
                        >
                            <LogOut className="size-4" />
                        </Button>
                    </div>
                </div>
            </header>

            <div className="mx-auto max-w-7xl px-4 py-5 sm:px-6 sm:py-7">
                <nav className="mb-6 flex gap-2 overflow-x-auto pb-1">
                    {navItems.map((item) => {
                        const active =
                            item.href === '/'
                                ? page.url === '/'
                                : page.url.startsWith(item.href);
                        const Icon = item.icon;

                        return (
                            <Link
                                key={item.href}
                                href={item.href}
                                className={cn(
                                    'inline-flex shrink-0 items-center gap-2 rounded-xl px-3 py-2 text-sm font-medium transition-colors',
                                    active
                                        ? 'bg-slate-950 text-white dark:bg-emerald-300 dark:text-slate-950'
                                        : 'bg-muted/60 text-muted-foreground hover:bg-muted hover:text-foreground',
                                )}
                            >
                                <Icon className="size-4" />
                                {item.title}
                            </Link>
                        );
                    })}
                </nav>

                <div className="mb-6">
                    <h1 className="text-2xl font-semibold tracking-tight sm:text-3xl">{title}</h1>
                    {description ? (
                        <p className="mt-1 max-w-3xl text-sm text-muted-foreground sm:text-base">
                            {description}
                        </p>
                    ) : null}
                </div>

                {children}
            </div>
        </div>
    );
}
