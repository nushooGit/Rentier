import { Form, Head, Link } from '@inertiajs/react';
import { ArrowLeft, Ban, CheckCircle2, ShieldCheck } from 'lucide-react';
import { useState } from 'react';
import InputError from '@/components/input-error';
import AdminLayout from '@/layouts/admin-layout';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { formatDateLong } from '@/lib/date';

type Actor = {
    id: number;
    name: string;
};

type WorkspaceSummary = {
    id: number;
    name: string;
    slug: string;
    is_personal: boolean;
    is_suspended: boolean;
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
    suspended_at: string | null;
    suspension_reason: string | null;
    suspended_by: Actor | null;
    reactivated_at: string | null;
    reactivated_by: Actor | null;
    can_suspend: boolean;
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
                description="Detalii operaționale și control de acces pentru contul Rentier."
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
                            <span
                                className={
                                    user.suspended_at
                                        ? 'rounded-full bg-red-100 px-2 py-1 text-xs font-medium text-red-800 dark:bg-red-400/10 dark:text-red-300'
                                        : 'rounded-full bg-emerald-100 px-2 py-1 text-xs font-medium text-emerald-800 dark:bg-emerald-400/10 dark:text-emerald-300'
                                }
                            >
                                {user.suspended_at ? 'Suspendat' : 'Activ'}
                            </span>
                        </div>

                        <dl className="mt-5 grid gap-4 text-sm sm:grid-cols-2">
                            <Detail label="Email" value={user.email} />
                            <Detail
                                label="Status email"
                                value={user.email_verified_at ? 'Verificat' : 'Neverificat'}
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
                            {user.reactivated_at ? (
                                <Detail
                                    label="Ultima reactivare"
                                    value={`${formatDateLong(user.reactivated_at)} · ${user.reactivated_by?.name ?? 'administrator'}`}
                                />
                            ) : null}
                        </dl>
                    </section>

                    <section className="grid grid-cols-2 gap-3">
                        <Metric label="Workspace-uri" value={stats.workspaces} />
                        <Metric
                            label="Workspace-uri deținute"
                            value={stats.owned_workspaces}
                        />
                    </section>
                </div>

                <UserAccessControl user={user} />

                <section className="mt-6 rounded-2xl border border-border/70 bg-card p-5 shadow-sm">
                    <h2 className="font-semibold">Acces la workspace-uri</h2>
                    <div className="mt-4 divide-y divide-border/70">
                        {memberships.map((membership) => (
                            <div
                                key={membership.workspace.id}
                                className="flex flex-col gap-2 py-3 first:pt-0 last:pb-0 sm:flex-row sm:items-center sm:justify-between"
                            >
                                <div>
                                    <div className="flex flex-wrap items-center gap-2">
                                        <Link
                                            href={`/workspaces/${membership.workspace.slug}`}
                                            className="font-medium hover:underline"
                                        >
                                            {membership.workspace.name}
                                        </Link>
                                        {membership.workspace.is_suspended ? (
                                            <span className="rounded-full bg-red-100 px-2 py-1 text-xs text-red-800 dark:bg-red-400/10 dark:text-red-300">
                                                Suspendat
                                            </span>
                                        ) : null}
                                    </div>
                                    <p className="text-sm text-muted-foreground">
                                        {membership.workspace.is_personal
                                            ? 'Workspace personal'
                                            : 'Workspace'}
                                    </p>
                                </div>
                                <div className="text-sm text-muted-foreground sm:text-right">
                                    <p>{roleLabels[membership.role]}</p>
                                    <p>Din {formatDateLong(membership.created_at)}</p>
                                </div>
                            </div>
                        ))}
                    </div>
                </section>
            </AdminLayout>
        </>
    );
}

function UserAccessControl({ user }: { user: AdminUser }) {
    const [open, setOpen] = useState(false);

    return (
        <section className="mt-6 rounded-2xl border border-border/70 bg-card p-5 shadow-sm">
            <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h2 className="font-semibold">Acces cont</h2>
                    {user.suspended_at ? (
                        <div className="mt-2 space-y-1 text-sm text-muted-foreground">
                            <p>
                                Suspendat la {formatDateLong(user.suspended_at)}
                                {user.suspended_by
                                    ? ` de ${user.suspended_by.name}`
                                    : ''}
                                .
                            </p>
                            <p className="whitespace-pre-wrap">
                                Motiv: {user.suspension_reason ?? 'Nespecificat'}
                            </p>
                        </div>
                    ) : (
                        <p className="mt-2 text-sm text-muted-foreground">
                            Contul se poate autentifica și folosi workspace-urile active.
                        </p>
                    )}
                    {user.is_platform_admin ? (
                        <p className="mt-2 text-sm text-muted-foreground">
                            Conturile platform admin nu pot fi suspendate din această interfață.
                        </p>
                    ) : null}
                </div>

                {user.can_suspend ? (
                    <Dialog open={open} onOpenChange={setOpen}>
                        <DialogTrigger asChild>
                            <Button
                                variant={user.suspended_at ? 'outline' : 'destructive'}
                            >
                                {user.suspended_at ? (
                                    <>
                                        <CheckCircle2 />
                                        Reactivează
                                    </>
                                ) : (
                                    <>
                                        <Ban />
                                        Suspendă
                                    </>
                                )}
                            </Button>
                        </DialogTrigger>
                        <DialogContent>
                            {user.suspended_at ? (
                                <>
                                    <DialogTitle>Reactivezi acest cont?</DialogTitle>
                                    <DialogDescription>
                                        Utilizatorul va putea să se autentifice din nou. Datele contului nu sunt modificate.
                                    </DialogDescription>
                                    <Form
                                        action={`/users/${user.id}/reactivate`}
                                        method="patch"
                                        options={{ preserveScroll: true }}
                                        onSuccess={() => setOpen(false)}
                                    >
                                        {({ processing }) => (
                                            <DialogFooter className="gap-2">
                                                <DialogClose asChild>
                                                    <Button type="button" variant="secondary">
                                                        Renunță
                                                    </Button>
                                                </DialogClose>
                                                <Button type="submit" disabled={processing}>
                                                    Reactivează contul
                                                </Button>
                                            </DialogFooter>
                                        )}
                                    </Form>
                                </>
                            ) : (
                                <>
                                    <DialogTitle>Suspenzi acest cont?</DialogTitle>
                                    <DialogDescription>
                                        Sesiunile active vor fi invalidate, iar utilizatorul nu se va mai putea autentifica până la reactivare.
                                    </DialogDescription>
                                    <Form
                                        action={`/users/${user.id}/suspend`}
                                        method="patch"
                                        options={{ preserveScroll: true }}
                                        resetOnSuccess
                                        onSuccess={() => setOpen(false)}
                                        className="space-y-4"
                                    >
                                        {({ processing, errors }) => (
                                            <>
                                                <div>
                                                    <label
                                                        htmlFor="suspension-reason"
                                                        className="text-sm font-medium"
                                                    >
                                                        Motiv
                                                    </label>
                                                    <textarea
                                                        id="suspension-reason"
                                                        name="reason"
                                                        required
                                                        maxLength={1000}
                                                        rows={4}
                                                        className="mt-2 w-full rounded-md border border-input bg-background px-3 py-2 text-sm outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]"
                                                        placeholder="Ex.: solicitare suport, încălcare termeni, verificare necesară"
                                                    />
                                                    <InputError
                                                        className="mt-2"
                                                        message={errors.reason}
                                                    />
                                                </div>
                                                <DialogFooter className="gap-2">
                                                    <DialogClose asChild>
                                                        <Button type="button" variant="secondary">
                                                            Renunță
                                                        </Button>
                                                    </DialogClose>
                                                    <Button
                                                        type="submit"
                                                        variant="destructive"
                                                        disabled={processing}
                                                    >
                                                        Suspendă contul
                                                    </Button>
                                                </DialogFooter>
                                            </>
                                        )}
                                    </Form>
                                </>
                            )}
                        </DialogContent>
                    </Dialog>
                ) : null}
            </div>
        </section>
    );
}

function Detail({ label, value }: { label: string; value: string }) {
    return (
        <div>
            <dt className="text-muted-foreground">{label}</dt>
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
