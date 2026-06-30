import { Head, Link, router, usePage } from '@inertiajs/react';
import { ArrowLeft, Pencil, Trash2 } from 'lucide-react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { leaseStatusLabel } from '@/pages/leases/labels';
import { destroy, edit, index, show } from '@/routes/leases';
import type { Lease } from '@/types';

type Props = {
    lease: Lease;
};

function formatValue(value?: string | number | null) {
    return value ?? 'Nesetat';
}

function formatMoney(amount?: string | null, currency = 'RON') {
    if (!amount) {
        return 'Nesetat';
    }

    return `${Number(amount).toLocaleString(undefined, {
        maximumFractionDigits: 2,
        minimumFractionDigits: 0,
    })} ${currency}`;
}

function Detail({
    label,
    value,
}: {
    label: string;
    value?: string | number | null;
}) {
    return (
        <div>
            <dt className="text-sm text-muted-foreground">{label}</dt>
            <dd className="mt-1 text-sm font-medium">{formatValue(value)}</dd>
        </div>
    );
}

export default function LeaseShow({ lease }: Props) {
    const { currentTeam } = usePage().props;
    const currentTeamSlug = currentTeam?.slug ?? '';

    const deleteLease = () => {
        if (!window.confirm(`Ștergi contractul pentru ${lease.renter.name}?`)) {
            return;
        }

        router.delete(destroy([currentTeamSlug, lease.id]).url);
    };

    return (
        <>
            <Head title={`Contract ${lease.renter.name}`} />

            <div className="mx-auto flex w-full max-w-6xl flex-col space-y-3.5 p-3 sm:p-4">
                <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div className="space-y-2">
                        <Heading
                            variant="small"
                            title={lease.renter.name}
                            description={`${lease.property.name}, ${lease.property.city}`}
                        />
                        <Badge variant="secondary">
                            {leaseStatusLabel(lease.status)}
                        </Badge>
                    </div>

                    <div className="flex flex-col-reverse gap-2 sm:flex-row">
                        <Button variant="outline" asChild>
                            <Link href={index(currentTeamSlug)}>
                                <ArrowLeft /> Înapoi
                            </Link>
                        </Button>
                        <Button asChild>
                            <Link href={edit([currentTeamSlug, lease.id])}>
                                <Pencil /> Editează
                            </Link>
                        </Button>
                        <Button
                            variant="destructive"
                            onClick={deleteLease}
                            data-test="lease-delete-button"
                        >
                            <Trash2 /> Șterge
                        </Button>
                    </div>
                </div>

                <section className="rounded-lg border p-3 sm:p-3.5">
                    <h2 className="text-base font-medium">Detalii contract</h2>
                    <dl className="mt-2.5 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                        <Detail
                            label="Proprietate"
                            value={lease.property.name}
                        />
                        <Detail
                            label="Status"
                            value={leaseStatusLabel(lease.status)}
                        />
                        <Detail label="Data început" value={lease.start_date} />
                        <Detail label="Data sfârșit" value={lease.end_date} />
                    </dl>
                </section>

                <section className="rounded-lg border p-3 sm:p-3.5">
                    <h2 className="text-base font-medium">Chiriaș</h2>
                    <dl className="mt-2.5 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                        <Detail
                            label="Nume chiriaș"
                            value={lease.renter.name}
                        />
                        <Detail
                            label="Email chiriaș"
                            value={lease.renter.email}
                        />
                        <Detail
                            label="Telefon chiriaș"
                            value={lease.renter.phone}
                        />
                    </dl>
                    {lease.renter.notes ? (
                        <p className="mt-3 text-sm whitespace-pre-wrap">
                            {lease.renter.notes}
                        </p>
                    ) : null}
                </section>

                <section className="rounded-lg border p-3 sm:p-3.5">
                    <h2 className="text-base font-medium">Setări chirie</h2>
                    <dl className="mt-2.5 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                        <Detail
                            label="Chirie lunară"
                            value={formatMoney(
                                lease.monthly_rent_amount,
                                lease.currency,
                            )}
                        />
                        <Detail label="Monedă" value={lease.currency} />
                        <Detail
                            label="Zi scadență"
                            value={lease.rent_due_day}
                        />
                        <Detail
                            label="Garanție"
                            value={formatMoney(
                                lease.deposit_amount,
                                lease.currency,
                            )}
                        />
                    </dl>
                </section>

                {lease.notes ? (
                    <section className="rounded-lg border p-3 sm:p-3.5">
                        <h2 className="text-base font-medium">Note interne</h2>
                        <p className="mt-2.5 text-sm whitespace-pre-wrap">
                            {lease.notes}
                        </p>
                    </section>
                ) : null}
            </div>
        </>
    );
}

LeaseShow.layout = (props: {
    currentTeam?: { slug: string } | null;
    lease: Lease;
}) => ({
    breadcrumbs: [
        {
            title: 'Contracte',
            href: props.currentTeam ? index(props.currentTeam.slug) : '/',
        },
        {
            title: props.lease.renter.name,
            href: props.currentTeam
                ? show([props.currentTeam.slug, props.lease.id])
                : '/',
        },
    ],
});
