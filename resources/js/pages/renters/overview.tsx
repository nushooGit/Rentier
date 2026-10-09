import { Head } from '@inertiajs/react';

type Lease = {
    id: number;
    property: string;
    startDate: string;
    endDate: string | null;
    rent: string;
    currency: string;
    dueDay: number | null;
    status: string;
};

export default function RenterOverview({ leases }: { leases: Lease[] }) {
    return (
        <main className="mx-auto min-h-screen max-w-3xl space-y-6 p-6">
            <Head title="Portal chiriaș · Rentier" />
            <h1 className="text-2xl font-semibold">Contractele mele</h1>
            <p className="text-sm text-muted-foreground">Aici apar doar contractele asociate contului tău.</p>
            {leases.length === 0 ? (
                <p>Nu ai încă niciun contract asociat acestui cont.</p>
            ) : (
                <div className="grid gap-4">
                    {leases.map((lease) => (
                        <section key={lease.id} className="rounded-xl border p-5">
                            <h2 className="font-semibold">{lease.property}</h2>
                            <p>Chirie lunară: {lease.rent} {lease.currency}</p>
                            <p>Ziua scadenței: {lease.dueDay ?? '—'}</p>
                            <p>Perioadă: {lease.startDate} – {lease.endDate ?? 'în curs'}</p>
                            <p>Status: {lease.status}</p>
                        </section>
                    ))}
                </div>
            )}
        </main>
    );
}
