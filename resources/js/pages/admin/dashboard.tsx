import { Head, Link } from '@inertiajs/react';
import { Building2, FileText, Home, Users } from 'lucide-react';
import AdminLayout from '@/layouts/admin-layout';
import { formatDateLong } from '@/lib/date';

type Stats = {
    users: number;
    workspaces: number;
    properties: number;
    leases: number;
};

type RecentUser = {
    id: number;
    name: string;
    email: string;
    created_at: string;
};

type RecentWorkspace = {
    id: number;
    name: string;
    slug: string;
    is_personal: boolean;
    members_count: number;
    properties_count: number;
    created_at: string;
};

export default function AdminDashboard({
    stats,
    recentUsers,
    recentWorkspaces,
}: {
    stats: Stats;
    recentUsers: RecentUser[];
    recentWorkspaces: RecentWorkspace[];
}) {
    return (
        <>
            <Head title="Admin" />
            <AdminLayout
                title="Prezentare platformă"
                description="Vizibilitate internă asupra conturilor și workspace-urilor Rentier. Acest prim increment este intenționat read-only."
            >
                <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                    <StatCard label="Utilizatori" value={stats.users} icon={Users} />
                    <StatCard label="Workspace-uri" value={stats.workspaces} icon={Building2} />
                    <StatCard label="Proprietăți" value={stats.properties} icon={Home} />
                    <StatCard label="Contracte" value={stats.leases} icon={FileText} />
                </div>

                <div className="mt-6 grid gap-4 lg:grid-cols-2">
                    <section className="rounded-2xl border border-border/70 bg-card p-4 shadow-sm sm:p-5">
                        <div className="flex items-center justify-between gap-3">
                            <h2 className="font-semibold">Utilizatori recenți</h2>
                            <Link href="/users" className="text-sm font-medium text-emerald-700 hover:underline dark:text-emerald-300">
                                Vezi toți
                            </Link>
                        </div>
                        <div className="mt-4 divide-y divide-border/70">
                            {recentUsers.map((user) => (
                                <div key={user.id} className="py-3 first:pt-0 last:pb-0">
                                    <p className="font-medium">{user.name}</p>
                                    <p className="text-sm text-muted-foreground">{user.email}</p>
                                    <p className="mt-1 text-xs text-muted-foreground">
                                        Creat {formatDateLong(user.created_at)}
                                    </p>
                                </div>
                            ))}
                        </div>
                    </section>

                    <section className="rounded-2xl border border-border/70 bg-card p-4 shadow-sm sm:p-5">
                        <div className="flex items-center justify-between gap-3">
                            <h2 className="font-semibold">Workspace-uri recente</h2>
                            <Link href="/workspaces" className="text-sm font-medium text-emerald-700 hover:underline dark:text-emerald-300">
                                Vezi toate
                            </Link>
                        </div>
                        <div className="mt-4 divide-y divide-border/70">
                            {recentWorkspaces.map((workspace) => (
                                <div key={workspace.id} className="py-3 first:pt-0 last:pb-0">
                                    <div className="flex items-center justify-between gap-3">
                                        <p className="font-medium">{workspace.name}</p>
                                        <span className="rounded-full bg-muted px-2 py-1 text-xs text-muted-foreground">
                                            {workspace.is_personal ? 'Personal' : 'Workspace'}
                                        </span>
                                    </div>
                                    <p className="text-sm text-muted-foreground">
                                        {workspace.members_count} membri · {workspace.properties_count} proprietăți
                                    </p>
                                    <p className="mt-1 text-xs text-muted-foreground">
                                        Creat {formatDateLong(workspace.created_at)}
                                    </p>
                                </div>
                            ))}
                        </div>
                    </section>
                </div>
            </AdminLayout>
        </>
    );
}

function StatCard({
    label,
    value,
    icon: Icon,
}: {
    label: string;
    value: number;
    icon: typeof Users;
}) {
    return (
        <section className="rounded-2xl border border-border/70 bg-card p-4 shadow-sm sm:p-5">
            <div className="flex items-center justify-between gap-3">
                <p className="text-sm font-medium text-muted-foreground">{label}</p>
                <span className="flex size-9 items-center justify-center rounded-xl bg-muted">
                    <Icon className="size-4" />
                </span>
            </div>
            <p className="mt-3 text-3xl font-semibold tracking-tight">{value}</p>
        </section>
    );
}
