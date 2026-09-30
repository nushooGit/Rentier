import { Head, Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import AdminLayout from '@/layouts/admin-layout';
import { formatDateLong } from '@/lib/date';

type Member = {
    id: number;
    name: string;
    email: string;
    role: 'owner' | 'admin' | 'member';
    joined_at: string;
};

type PropertySummary = {
    id: number;
    name: string;
    city: string;
    county_or_sector: string | null;
    status: string;
    monthly_rent_amount: string | null;
    currency: string;
    leases_count: number;
};

const roleLabels: Record<Member['role'], string> = {
    owner: 'Proprietar',
    admin: 'Administrator',
    member: 'Membru',
};

export default function AdminWorkspaceShow({
    workspace,
    stats,
    members,
    properties,
    properties_truncated,
}: {
    workspace: {
        id: number;
        name: string;
        slug: string;
        is_personal: boolean;
        created_at: string;
    };
    stats: {
        members: number;
        properties: number;
        leases: number;
        renters: number;
        payments: number;
        expenses: number;
    };
    members: Member[];
    properties: PropertySummary[];
    properties_truncated: boolean;
}) {
    return (
        <>
            <Head title={`Admin · ${workspace.name}`} />
            <AdminLayout
                title={workspace.name}
                description="Detalii operaționale read-only pentru workspace."
            >
                <Link
                    href="/workspaces"
                    className="mb-5 inline-flex items-center gap-2 text-sm font-medium text-muted-foreground hover:text-foreground"
                >
                    <ArrowLeft className="size-4" />
                    Înapoi la workspace-uri
                </Link>

                <section className="rounded-2xl border border-border/70 bg-card p-5 shadow-sm">
                    <div className="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <p className="font-medium">
                                {workspace.is_personal
                                    ? 'Workspace personal'
                                    : 'Workspace'}
                            </p>
                            <p className="mt-1 text-sm text-muted-foreground">
                                /{workspace.slug}
                            </p>
                        </div>
                        <p className="text-sm text-muted-foreground">
                            Creat {formatDateLong(workspace.created_at)}
                        </p>
                    </div>
                </section>

                <section className="mt-4 grid grid-cols-2 gap-3 md:grid-cols-3 xl:grid-cols-6">
                    <Metric label="Membri" value={stats.members} />
                    <Metric label="Proprietăți" value={stats.properties} />
                    <Metric label="Contracte" value={stats.leases} />
                    <Metric label="Chiriași" value={stats.renters} />
                    <Metric label="Plăți" value={stats.payments} />
                    <Metric label="Cheltuieli" value={stats.expenses} />
                </section>

                <div className="mt-6 grid gap-4 lg:grid-cols-2">
                    <section className="rounded-2xl border border-border/70 bg-card p-5 shadow-sm">
                        <h2 className="font-semibold">Membri</h2>
                        <div className="mt-4 divide-y divide-border/70">
                            {members.map((member) => (
                                <div
                                    key={member.id}
                                    className="flex flex-col gap-2 py-3 first:pt-0 last:pb-0 sm:flex-row sm:items-start sm:justify-between"
                                >
                                    <div className="min-w-0">
                                        <Link
                                            href={`/users/${member.id}`}
                                            className="font-medium hover:underline"
                                        >
                                            {member.name}
                                        </Link>
                                        <p className="break-all text-sm text-muted-foreground">
                                            {member.email}
                                        </p>
                                    </div>
                                    <div className="text-sm text-muted-foreground sm:text-right">
                                        <p>{roleLabels[member.role]}</p>
                                        <p>
                                            Din {formatDateLong(member.joined_at)}
                                        </p>
                                    </div>
                                </div>
                            ))}
                        </div>
                    </section>

                    <section className="rounded-2xl border border-border/70 bg-card p-5 shadow-sm">
                        <div className="flex items-center justify-between gap-3">
                            <h2 className="font-semibold">Proprietăți</h2>
                            {properties_truncated ? (
                                <span className="text-xs text-muted-foreground">
                                    Primele 100
                                </span>
                            ) : null}
                        </div>
                        <div className="mt-4 divide-y divide-border/70">
                            {properties.length === 0 ? (
                                <p className="text-sm text-muted-foreground">
                                    Nicio proprietate.
                                </p>
                            ) : (
                                properties.map((property) => (
                                    <div
                                        key={property.id}
                                        className="py-3 first:pt-0 last:pb-0"
                                    >
                                        <div className="flex items-start justify-between gap-3">
                                            <div>
                                                <p className="font-medium">
                                                    {property.name}
                                                </p>
                                                <p className="text-sm text-muted-foreground">
                                                    {property.city}
                                                    {property.county_or_sector
                                                        ? ` · ${property.county_or_sector}`
                                                        : ''}
                                                </p>
                                            </div>
                                            <span className="rounded-full bg-muted px-2 py-1 text-xs text-muted-foreground">
                                                {property.leases_count} contracte
                                            </span>
                                        </div>
                                        {property.monthly_rent_amount ? (
                                            <p className="mt-1 text-sm text-muted-foreground">
                                                Chirie configurată:{' '}
                                                {property.monthly_rent_amount}{' '}
                                                {property.currency}
                                            </p>
                                        ) : null}
                                    </div>
                                ))
                            )}
                        </div>
                    </section>
                </div>
            </AdminLayout>
        </>
    );
}

function Metric({ label, value }: { label: string; value: number }) {
    return (
        <div className="rounded-2xl border border-border/70 bg-card p-4 shadow-sm">
            <p className="text-xs text-muted-foreground">{label}</p>
            <p className="mt-1 text-2xl font-semibold tracking-tight">{value}</p>
        </div>
    );
}
