import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, ShieldCheck } from 'lucide-react';
import AdminLayout from '@/layouts/admin-layout';
import { formatDateLong } from '@/lib/date';

type WorkspaceSummary = {
    id: number;
    name: string;
    slug: string;
    is_personal: boolean;
};

type Membership = {
    workspace: WorkspaceSummary;
    role: 'owner' | 'admin' | 'member';
    created_at: string;
};

type AdminUser = {
    id: number;
    name: string;
    email: string;
    email_verified_at: string | null;
    created_at: string;
    is_platform_admin: boolean;
    two_factor_enabled: boolean;
    current_workspace: {
        id: number;
        name: string;
        slug: string;
    } | null;
};

const roleLabels: Record<Membership['role'], string> = {
    owner: 'Proprietar',
    admin: 'Administrator',
    member: 'Membru',
};

export default function AdminUserShow({
    user,
    stats,
    memberships,
}: {
    user: AdminUser;
    stats: { workspaces: number; owned_workspaces: number };
    memberships: Membership[];
}) {
    return (
        <>
            <Head title={`Admin · ${user.name}`} />
            <AdminLayout
                title={user.name}
                description="Detalii operaționale read-only pentru contul Rentier."
            >
                <Link
                    href="/users"
                    className="mb-5 inline-flex items-center gap-2 text-sm font-medium text-muted-foreground hover:text-foreground"
                >
                    <ArrowLeft className="size-4" />
                    Înapoi la utilizatori
                </Link>

                <div className="grid gap-4 lg:grid-cols-[1.1fr_0.9fr]">
                    <section className="rounded-2xl border border-border/70 bg-card p-5 shadow-sm">
                        <div className="flex flex-wrap items-center gap-2">
                            <h2 className="text-lg font-semibold">Cont</h2>
                            {user.is_platform_admin ? (
                                <span className="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2 py-1 text-xs font-medium text-emerald-800 dark:bg-emerald-400/10 dark:text-emerald-300">
                                    <ShieldCheck className="size-3" />
                                    Platform admin
                                </span>
                            ) : null}
                        </div>

                        <dl className="mt-5 grid gap-4 text-sm sm:grid-cols-2">
                            <Detail label="Email" value={user.email} />
                            <Detail
                                label="Email"
                                value={user.email_verified_at ? 'Verificat' : 'Neverificat'}
                                secondary
                            />
                            <Detail
                                label="Creat"
                                value={formatDateLong(user.created_at)}
                            />
                            <Detail
                                label="Autentificare în doi pași"
                                value={user.two_factor_enabled ? 'Activată' : 'Neactivată'}
                            />
                            <Detail
                                label="Workspace curent"
                                value={user.current_workspace?.name ?? 'Nesetat'}
                            />
                        </dl>
                    </section>

                    <section className="grid grid-cols-2 gap-3">
                        <Metric label="Workspace-uri" value={stats.workspaces} />
                        <Metric
                            label="Deținute"
                            value={stats.owned_workspaces}
                        />
                    </section>
                </div>

                <section className="mt-6 rounded-2xl border border-border/70 bg-card p-5 shadow-sm">
                    <h2 className="font-semibold">Acces la workspace-uri</h2>
                    <div className="mt-4 divide-y divide-border/70">
                        {memberships.map((membership) => (
                            <div
                                key={membership.workspace.id}
                                className="flex flex-col gap-2 py-3 first:pt-0 last:pb-0 sm:flex-row sm:items-center sm:justify-between"
                            >
                                <div>
                                    <Link
                                        href={`/workspaces/${membership.workspace.slug}`}
                                        className="font-medium hover:underline"
                                    >
                                        {membership.workspace.name}
                                    </Link>
                                    <p className="text-sm text-muted-foreground">
                                        {membership.workspace.is_personal
                                            ? 'Workspace personal'
                                            : 'Workspace'}
                                    </p>
                                </div>
                                <div className="text-sm text-muted-foreground sm:text-right">
                                    <p>{roleLabels[membership.role]}</p>
                                    <p>
                                        Din {formatDateLong(membership.created_at)}
                                    </p>
                                </div>
                            </div>
                        ))}
                    </div>
                </section>
            </AdminLayout>
        </>
    );
}

function Detail({
    label,
    value,
    secondary = false,
}: {
    label: string;
    value: string;
    secondary?: boolean;
}) {
    return (
        <div>
            <dt className="text-muted-foreground">{secondary ? 'Status email' : label}</dt>
            <dd className="mt-1 break-words font-medium">{value}</dd>
        </div>
    );
}

function Metric({ label, value }: { label: string; value: number }) {
    return (
        <div className="rounded-2xl border border-border/70 bg-card p-5 shadow-sm">
            <p className="text-sm text-muted-foreground">{label}</p>
            <p className="mt-2 text-3xl font-semibold tracking-tight">{value}</p>
        </div>
    );
}
