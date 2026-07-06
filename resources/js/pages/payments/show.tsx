import { Head, Link, router, usePage } from '@inertiajs/react';
import { ArrowLeft, Pencil, Trash2 } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { formatDateLong } from '@/lib/date';
import { formatMoney } from '@/lib/money';
import { paymentMethodLabel } from '@/pages/payments/labels';
import { destroy, edit, index, show } from '@/routes/payments';
import type { RentPayment } from '@/types';

type Props = {
    payment: RentPayment;
};

const monthNames = [
    'Ianuarie',
    'Februarie',
    'Martie',
    'Aprilie',
    'Mai',
    'Iunie',
    'Iulie',
    'August',
    'Septembrie',
    'Octombrie',
    'Noiembrie',
    'Decembrie',
];

function formatRentPeriod(month: number | null, year: number | null) {
    if (month === null || year === null) {
        return 'Fără perioadă de chirie';
    }

    return `${monthNames[month - 1] ?? month} ${year}`;
}

function contractLabel(payment: RentPayment) {
    return `${payment.property.name} - ${payment.renter.name}`;
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
            <dd className="mt-1 text-sm font-medium">{value ?? 'Nesetat'}</dd>
        </div>
    );
}

export default function PaymentShow({ payment }: Props) {
    const { currentTeam } = usePage().props;
    const currentTeamSlug = currentTeam?.slug ?? '';

    const deletePayment = () => {
        if (!window.confirm(`Ștergi plata pentru ${payment.renter.name}?`)) {
            return;
        }

        router.delete(destroy([currentTeamSlug, payment.id]).url);
    };

    return (
        <>
            <Head title={`Plată ${payment.renter.name}`} />
            <div className="mx-auto flex w-full max-w-6xl flex-col space-y-3.5 p-3 sm:p-4">
                <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div className="space-y-2">
                        <p className="text-sm text-muted-foreground">
                            Sumă încasată
                        </p>
                        <div className="flex flex-wrap items-center gap-2">
                            <h1 className="text-2xl font-semibold tracking-normal">
                                {formatMoney(payment.amount, payment.currency)}
                            </h1>
                            <Badge variant="secondary">
                                {payment.status_summary.status_label}
                            </Badge>
                        </div>
                        <p className="text-sm text-muted-foreground">
                            {contractLabel(payment)}
                        </p>
                    </div>
                    <div className="flex flex-col-reverse gap-2 sm:flex-row">
                        <Button variant="outline" asChild>
                            <Link href={index(currentTeamSlug)}>
                                <ArrowLeft /> Înapoi
                            </Link>
                        </Button>
                        <Button asChild>
                            <Link href={edit([currentTeamSlug, payment.id])}>
                                <Pencil /> Editează
                            </Link>
                        </Button>
                        <Button variant="destructive" onClick={deletePayment}>
                            <Trash2 /> Șterge
                        </Button>
                    </div>
                </div>

                <section className="rounded-lg border p-3 sm:p-3.5">
                    <h2 className="text-base font-medium">Detalii plată</h2>
                    <dl className="mt-2.5 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                        <Detail
                            label="Contract"
                            value={contractLabel(payment)}
                        />
                        <Detail
                            label="Proprietate"
                            value={payment.property.name}
                        />
                        <Detail label="Chiriaș" value={payment.renter.name} />
                        <Detail
                            label="Metodă"
                            value={paymentMethodLabel(payment.method)}
                        />
                        <Detail
                            label="Data încasării"
                            value={formatDateLong(payment.payment_date)}
                        />
                        <Detail
                            label="Perioada chiriei"
                            value={formatRentPeriod(
                                payment.period_month,
                                payment.period_year,
                            )}
                        />
                        <Detail
                            label="Status"
                            value={payment.status_summary.status_label}
                        />
                    </dl>
                </section>

                {payment.notes ? (
                    <section className="rounded-lg border p-3 sm:p-3.5">
                        <h2 className="text-base font-medium">Note</h2>
                        <p className="mt-2.5 text-sm whitespace-pre-wrap">
                            {payment.notes}
                        </p>
                    </section>
                ) : null}
            </div>
        </>
    );
}

PaymentShow.layout = (props: {
    currentTeam?: { slug: string } | null;
    payment: RentPayment;
}) => ({
    breadcrumbs: [
        {
            title: 'Plăți',
            href: props.currentTeam ? index(props.currentTeam.slug) : '/',
        },
        {
            title: props.payment.renter.name,
            href: props.currentTeam
                ? show([props.currentTeam.slug, props.payment.id])
                : '/',
        },
    ],
});
