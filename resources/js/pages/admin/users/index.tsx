import { Head } from '@inertiajs/react';
import { ShieldCheck } from 'lucide-react';
import AdminLayout from '@/layouts/admin-layout';
import { formatDateLong } from '@/lib/date';

type AdminUser = {
    id: number;
    name: string;
    email: string;
    email_verified_at: string | null;
    created_at: string;
    workspaces_count: number;
    is_platform_admin: boolean;
};

export default function AdminUsers({ users }: { users: AdminUser[] }) {
    return (
        <>
            <Head title="Admin · Utilizatori" />
            <AdminLayout
                title="Utilizatori"
                description="Primele 200 de conturi, în ordine descrescătoare după creare. Acțiunile de suspendare/reactivare nu sunt activate în acest increment."
            >
                <div className="space-y-3">
                    {users.map((user) => (
                        <article
                            key={user.id}
                            className="rounded-2xl border border-border/70 bg-card p-4 shadow-sm sm:p-5"
                        >
                            <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                <div className="min-w-0">
                                    <div className="flex flex-wrap items-center gap-2">
                                        <h2 className="truncate font-semibold">{user.name}</h2>
                                        {user.is_platform_admin ? (
                                            <span className="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2 py-1 text-xs font-medium text-emerald-800 dark:bg-emerald-400/10 dark:text-emerald-300">
                                                <ShieldCheck className="size-3" />
                                                Platform admin
                                            </span>
                                        ) : null}
                                        <span className="rounded-full bg-muted px-2 py-1 text-xs text-muted-foreground">
                                            {user.email_verified_at ? 'Email verificat' : 'Email neverificat'}
                                        </span>
                                    </div>
                                    <p className="mt-1 break-all text-sm text-muted-foreground">{user.email}</p>
                                </div>
                                <div className="text-sm text-muted-foreground sm:text-right">
                                    <p>{user.workspaces_count} workspace-uri</p>
                                    <p>Creat {formatDateLong(user.created_at)}</p>
                                </div>
                            </div>
                        </article>
                    ))}
                </div>
            </AdminLayout>
        </>
    );
}
