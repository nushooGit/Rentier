import { Head } from '@inertiajs/react';
import AdminLayout from '@/layouts/admin-layout';
import { formatDateLong } from '@/lib/date';

type AdminWorkspace = {
    id: number;
    name: string;
    slug: string;
    is_personal: boolean;
    created_at: string;
    members_count: number;
    properties_count: number;
    leases_count: number;
};

export default function AdminWorkspaces({
    workspaces,
}: {
    workspaces: AdminWorkspace[];
}) {
    return (
        <>
            <Head title="Admin · Workspace-uri" />
            <AdminLayout
                title="Workspace-uri"
                description="Primele 200 de workspace-uri active în baza de date. Suspendarea/reactivarea este rezervată următorului increment, după revizuirea migrării și a regulilor de blocare."
            >
                <div className="space-y-3">
                    {workspaces.map((workspace) => (
                        <article
                            key={workspace.id}
                            className="rounded-2xl border border-border/70 bg-card p-4 shadow-sm sm:p-5"
                        >
                            <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                <div className="min-w-0">
                                    <div className="flex flex-wrap items-center gap-2">
                                        <h2 className="truncate font-semibold">{workspace.name}</h2>
                                        <span className="rounded-full bg-muted px-2 py-1 text-xs text-muted-foreground">
                                            {workspace.is_personal ? 'Personal' : 'Workspace'}
                                        </span>
                                    </div>
                                    <p className="mt-1 truncate text-sm text-muted-foreground">/{workspace.slug}</p>
                                </div>
                                <p className="text-sm text-muted-foreground sm:text-right">
                                    Creat {formatDateLong(workspace.created_at)}
                                </p>
                            </div>

                            <div className="mt-4 grid grid-cols-3 gap-2 text-center text-sm">
                                <Metric label="Membri" value={workspace.members_count} />
                                <Metric label="Proprietăți" value={workspace.properties_count} />
                                <Metric label="Contracte" value={workspace.leases_count} />
                            </div>
                        </article>
                    ))}
                </div>
            </AdminLayout>
        </>
    );
}

function Metric({ label, value }: { label: string; value: number }) {
    return (
        <div className="rounded-xl bg-muted/60 p-3">
            <p className="text-lg font-semibold">{value}</p>
            <p className="text-xs text-muted-foreground">{label}</p>
        </div>
    );
}
