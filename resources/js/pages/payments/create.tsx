import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import PaymentForm from '@/pages/payments/form';
import { translateKey, useI18n } from '@/lib/i18n';
import { index, store } from '@/routes/payments';
import type { PaymentLeaseOption, PaymentMethod, PaymentOption } from '@/types';

type Props = {
    leases: PaymentLeaseOption[];
    paymentMethods: PaymentOption<PaymentMethod>[];
};

export default function PaymentCreate({ leases, paymentMethods }: Props) {
    const { currentTeam } = usePage().props;
    const { t } = useI18n();
    const currentTeamSlug = currentTeam?.slug ?? '';

    return (
        <>
            <Head title={t('payments.create.title')} />
            <div className="mx-auto flex w-full max-w-[1180px] flex-col gap-5 p-3 sm:p-5 lg:p-6">
                <div className="flex flex-col gap-4 rounded-2xl border border-border/70 bg-card/75 p-4 shadow-sm sm:flex-row sm:items-center sm:justify-between sm:p-5">
                    <Heading
                        variant="small"
                        title={t('payments.create.title')}
                        description={t('payments.create.description')}
                    />
                    <Button variant="outline" asChild>
                        <Link href={index(currentTeamSlug)}>
                            <ArrowLeft /> {t('common.back')}
                        </Link>
                    </Button>
                </div>

                {leases.length === 0 ? (
                    <div className="rounded-2xl border border-dashed border-border bg-card/50 p-5 text-sm text-muted-foreground">
                        {t('payments.create.requiresLease')}
                    </div>
                ) : null}

                <PaymentForm
                    action={{
                        action: store(currentTeamSlug).url,
                        method: 'post',
                    }}
                    submitLabel={t('common.save')}
                    leases={leases}
                    paymentMethods={paymentMethods}
                />
            </div>
        </>
    );
}

PaymentCreate.layout = (props: { currentTeam?: { slug: string } | null }) => ({
    breadcrumbs: [
        {
            title: translateKey('nav.payments'),
            href: props.currentTeam ? index(props.currentTeam.slug) : '/',
        },
        {
            title: translateKey('payments.create.title'),
            href: props.currentTeam ? '#' : '/',
        },
    ],
});
