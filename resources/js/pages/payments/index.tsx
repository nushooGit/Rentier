import { Head, Link, router, usePage } from '@inertiajs/react';
import { Eye, Pencil, Plus, Trash2, WalletCards } from 'lucide-react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    paymentMethodLabel,
    paymentStatusLabel,
} from '@/pages/payments/labels';
import { create, destroy, edit, index, show } from '@/routes/payments';
import type { PaymentOption, PaymentStatus, RentPayment } from '@/types';

type Props = {
    payments: RentPayment[];
    paymentStatuses: PaymentOption<PaymentStatus>[];
};

function formatMoney(amount?: string | null, currency = 'RON') {
    if (!amount) {
        return 'Nesetat';
    }

    return `${Number(amount).toLocaleString('ro-RO', {
        maximumFractionDigits: 2,
        minimumFractionDigits: 0,
    })} ${currency}`;
}

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

function formatRentPeriod(month: number, year: number) {
    return `${monthNames[month - 1] ?? month} ${year}`;
}

function formatDate(date: string) {
    const [year, month, day] = date.split('-');

    return day && month && year ? `${day}.${month}.${year}` : date;
}

export default function PaymentsIndex({ payments }: Props) {
    const { currentTeam } = usePage().props;
    const currentTeamSlug = currentTeam?.slug ?? '';

    const deletePayment = (payment: RentPayment) => {
        if (
            !window.confirm(
                'Sigur vrei să ștergi această plată? Acțiunea nu poate fi anulată.',
            )
        ) {
            return;
        }

        router.delete(destroy([currentTeamSlug, payment.id]).url);
    };

    return (
        <>
            <Head title="Plăți" />

            <div className="mx-auto flex w-full max-w-7xl flex-col space-y-3.5 p-3 sm:p-4">
                <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <Heading
                        variant="small"
                        title="Plăți"
                        description="Urmărește încasările manuale pentru contracte"
                    />
                    <Button asChild data-test="payment-create-link">
                        <Link href={create(currentTeamSlug)}>
                            <Plus /> Plată nouă
                        </Link>
                    </Button>
                </div>

                {payments.length > 0 ? (
                    <div className="grid gap-2.5 md:grid-cols-2 xl:grid-cols-3">
                        {payments.map((payment) => (
                            <article
                                key={payment.id}
                                className="flex flex-col gap-2.5 rounded-lg border p-3"
                                data-test="payment-card"
                            >
                                <div className="flex items-start justify-between gap-3">
                                    <div className="min-w-0">
                                        <h2 className="truncate text-base font-medium">
                                            {payment.renter.name}
                                        </h2>
                                        <p className="mt-1 text-sm text-muted-foreground">
                                            {payment.property.name} ·{' '}
                                            {formatRentPeriod(
                                                payment.period_month,
                                                payment.period_year,
                                            )}
                                        </p>
                                    </div>
                                    <Badge variant="secondary">
                                        {paymentStatusLabel(payment.status)}
                                    </Badge>
                                </div>
                                <div className="grid gap-0.5 text-sm">
                                    <span className="font-medium">
                                        {formatMoney(
                                            payment.amount,
                                            payment.currency,
                                        )}
                                    </span>
                                    <span className="text-muted-foreground">
                                        {formatDate(payment.payment_date)} ·{' '}
                                        {paymentMethodLabel(payment.method)}
                                    </span>
                                </div>
                                <div className="mt-auto flex justify-end gap-2">
                                    <Button variant="ghost" size="sm" asChild>
                                        <Link
                                            href={show([
                                                currentTeamSlug,
                                                payment.id,
                                            ])}
                                        >
                                            <Eye className="h-4 w-4" />
                                        </Link>
                                    </Button>
                                    <Button variant="ghost" size="sm" asChild>
                                        <Link
                                            href={edit([
                                                currentTeamSlug,
                                                payment.id,
                                            ])}
                                        >
                                            <Pencil className="h-4 w-4" />
                                        </Link>
                                    </Button>
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        type="button"
                                        onClick={() => deletePayment(payment)}
                                        aria-label="Șterge plata"
                                        data-test="payment-delete-button"
                                    >
                                        <Trash2 className="h-4 w-4" />
                                    </Button>
                                </div>
                            </article>
                        ))}
                    </div>
                ) : (
                    <div className="rounded-lg border border-dashed p-5 text-center sm:p-6">
                        <WalletCards className="mx-auto h-8 w-8 text-muted-foreground" />
                        <h2 className="mt-3 text-base font-medium">
                            Nu există plăți încă
                        </h2>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Adaugă o plată pentru un contract existent.
                        </p>
                        <Button className="mt-4" asChild>
                            <Link href={create(currentTeamSlug)}>
                                <Plus /> Plată nouă
                            </Link>
                        </Button>
                    </div>
                )}
            </div>
        </>
    );
}

PaymentsIndex.layout = (props: { currentTeam?: { slug: string } | null }) => ({
    breadcrumbs: [
        {
            title: 'Plăți',
            href: props.currentTeam ? index(props.currentTeam.slug) : '/',
        },
    ],
});
