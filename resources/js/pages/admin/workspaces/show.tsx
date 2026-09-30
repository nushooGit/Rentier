import { Form, Head, Link } from '@inertiajs/react';
import { ArrowLeft, Ban, CheckCircle2 } from 'lucide-react';
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

type Member = {
    id: number;
    name: string;
    email: string;
    role: 'owner' | 'admin' | 'member';
    joined_at: string;
    is_suspended: boolean;
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

type Workspace = {
    id: number;
    name: string;
    slug: string;
    is_personal: boolean;
    created_at: string;
    suspended_at: string | null;
    suspension_reason: string | null;
    suspended_by: Actor | null;
    reactivated_at: string | null;
    reactivated_by: Actor | null;
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
    workspace: Workspace;
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
                description="Detalii operaționale și control de acces pentru workspace."
            >
                <Link
                    href="/workspaces"
                    className="mb-5 inline-flex items-center gap-2 text-sm font-medium text-muted-foreground hover:text-foreground"
                >
                    <ArrowLeft className="size-4" />
                    Înapoi la workspace-uri
                </Link>

                <section className="rounded-2xl border border-border/70 bg-card p-5 shadow-sm">
                    <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <div className="flex flex-wrap items-center gap-2">
                                <p className="font-medium">
                                    {workspace.is_personal
                                        ? 'Workspace personal'
                                        : 'Workspace'}
                                </p>
                                <span
                                    className={
                                        workspace.suspended_at
                                            ? 'rounded-full bg-red-100 px-2 py-1 text-xs font-medium text-red-800 dark:bg-red-400/10 dark:text-red-300'
                                            : 'rounded-full bg-emerald-100 px-2 py-1 text-xs font-medium text-emerald-800 dark:bg-emerald-400/10 dark:text-emerald-300'
                                    }
                                >
                                    {workspace.suspended_at ? 'Suspendat' : 'Activ'}
                                </span>
                            </div>
                            <p className="mt-1 text-sm text-muted-foreground">
                                /{workspace.slug}
                            </p>
                        </div>
                        <p className="text-sm text-muted-foreground">
                            Creat {formatDateLong(workspace.created_at)}
                        </p>
                    </div>
                </section>

                <WorkspaceAccessControl workspace={workspace} />

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
                                        <div className="flex flex-wrap items-center gap-2">
                                            <Link
                                                href={`/users/${member.id}`}
                                                className="font-medium hover:underline"
                                            >
                                                {member.name}
                                            </Link>
                                            {member.is_suspended ? (
                                                <span className="rounded-full bg-red-100 px-2 py-1 text-xs text-red-800 dark:bg-red-400/10 dark:text-red-300">
                                                    Suspendat
                                                </span>
                                            ) : null}
                                        </div>
                                        <p className="break-all text-sm text-muted-foreground">
                                            {member.email}
                                        </p>
                                    </div>
                                    <div className="text-sm text-muted-foreground sm:text-right">
                                        <p>{roleLabels[member.role]}</p>
                                        <p>Din {formatDateLong(member.joined_at)}</p>
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

function WorkspaceAccessControl({ workspace }: { workspace: Workspace }) {
    const [open, setOpen] = useState(false);

    return (
        <section className="mt-4 rounded-2xl border border-border/70 bg-card p-5 shadow-sm">
            <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h2 className="font-semibold">Acces workspace</h2>
                    {workspace.suspended_at ? (
                        <div className="mt-2 space-y-1 text-sm text-muted-foreground">
                            <p>
                                Suspendat la {formatDateLong(workspace.suspended_at)}
                                {workspace.suspended_by
                                    ? ` de ${workspace.suspended_by.name}`
                                    : ''}
                                .
                            </p>
                            <p className="whitespace-pre-wrap">
                                Motiv: {workspace.suspension_reason ?? 'Nespecificat'}
                            </p>
                        </div>
                    ) : (
                        <p className="mt-2 text-sm text-muted-foreground">
                            Membrii pot opera în acest workspace conform rolurilor lor.
                        </p>
                    )}
                    {workspace.reactivated_at ? (
                        <p className="mt-2 text-xs text-muted-foreground">
                            Ultima reactivare: {formatDateLong(workspace.reactivated_at)}
                            {workspace.reactivated_by
                                ? ` de ${workspace.reactivated_by.name}`
                                : ''}
                            .
                        </p>
                    ) : null}
                </div>

                <Dialog open={open} onOpenChange={setOpen}>
                    <DialogTrigger asChild>
                        <Button
                            variant={workspace.suspended_at ? 'outline' : 'destructive'}
                        >
                            {workspace.suspended_at ? (
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
                        {workspace.suspended_at ? (
                            <>
                                <DialogTitle>Reactivezi workspace-ul?</DialogTitle>
                                <DialogDescription>
                                    Membrii vor putea folosi din nou datele și fluxurile acestui workspace.
                                </DialogDescription>
                                <Form
                                    action={`/workspaces/${workspace.slug}/reactivate`}
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
                                                Reactivează workspace-ul
                                            </Button>
                                        </DialogFooter>
                                    )}
                                </Form>
                            </>
                        ) : (
                            <>
                                <DialogTitle>Suspenzi workspace-ul?</DialogTitle>
                                <DialogDescription>
                                    Datele rămân intacte, dar membrii nu vor putea opera în acest workspace până la reactivare.
                                </DialogDescription>
                                <Form
                                    action={`/workspaces/${workspace.slug}/suspend`}
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
                                                    htmlFor="workspace-suspension-reason"
                                                    className="text-sm font-medium"
                                                >
                                                    Motiv
                                                </label>
                                                <textarea
                                                    id="workspace-suspension-reason"
                                                    name="reason"
                                                    required
                                                    maxLength={1000}
                                                    rows={4}
                                                    className="mt-2 w-full rounded-md border border-input bg-background px-3 py-2 text-sm outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]"
                                                    placeholder="Ex.: verificare necesară, acces blocat temporar"
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
                                                    Suspendă workspace-ul
                                                </Button>
                                            </DialogFooter>
                                        </>
                                    )}
                                </Form>
                            </>
                        )}
                    </DialogContent>
                </Dialog>
            </div>
        </section>
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
