import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import PaymentForm from '@/pages/payments/form';
import { useI18n } from '@/lib/i18n';
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
    const { t } = useI18n();
    const currentTeamSlug = currentTeam?.slug ?? '';

    return (
        <>
            <Head title={`${t('payments.edit.title')}: ${payment.renter.name}`} />
            <div className="mx-auto flex w-full max-w-[1180px] flex-col gap-5 p-3 sm:p-5 lg:p-6">
                <div className="flex flex-col gap-4 rounded-2xl border border-border/70 bg-card/75 p-4 shadow-sm sm:flex-row sm:items-center sm:justify-between sm:p-5">
                    <Heading
                        variant="small"
                        title={t('payments.edit.title')}
                        description={`${payment.renter.name} - ${payment.property.name}`}
                    />
                    <Button variant="outline" asChild>
                        <Link href={show([currentTeamSlug, payment.id])}>
                            <ArrowLeft /> {t('common.back')}
                        </Link>
                    </Button>
                </div>

                <PaymentForm
                    action={{
                        action: update([currentTeamSlug, payment.id]).url,
                        method: 'patch',
                    }}
                    submitLabel={t('common.save')}
                    payment={payment}
                    leases={leases}
                    paymentMethods={paymentMethods}
                />
            </div>
        </>
    );
}
