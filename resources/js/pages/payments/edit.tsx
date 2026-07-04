import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import PaymentForm from '@/pages/payments/form';
import { show, update } from '@/routes/payments';
import type {
    PaymentLeaseOption,
    PaymentMethod,
    PaymentOption,
    RentPayment,
} from '@/types';

type Props = {
    payment: RentPayment;
    leases: PaymentLeaseOption[];
    paymentMethods: PaymentOption<PaymentMethod>[];
};

export default function PaymentEdit({
    payment,
    leases,
    paymentMethods,
}: Props) {
    const { currentTeam } = usePage().props;
    const currentTeamSlug = currentTeam?.slug ?? '';

    return (
        <>
            <Head title={`Editează plata ${payment.renter.name}`} />
            <div className="mx-auto flex w-full max-w-6xl flex-col space-y-3.5 p-3 sm:p-4">
                <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <Heading
                        variant="small"
                        title="Editează plata"
                        description={`${payment.renter.name} - ${payment.property.name}`}
                    />
                    <Button variant="outline" asChild>
                        <Link href={show([currentTeamSlug, payment.id])}>
                            <ArrowLeft /> Înapoi
                        </Link>
                    </Button>
                </div>

                <PaymentForm
                    action={{
                        action: update([currentTeamSlug, payment.id]).url,
                        method: 'patch',
                    }}
                    submitLabel="Salvează"
                    payment={payment}
                    leases={leases}
                    paymentMethods={paymentMethods}
                />
            </div>
        </>
    );
}
