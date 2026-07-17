import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import PaymentForm from '@/pages/payments/form';
import { index, store } from '@/routes/payments';
import type { PaymentLeaseOption, PaymentMethod, PaymentOption } from '@/types';

type Props = {
    leases: PaymentLeaseOption[];
    paymentMethods: PaymentOption<PaymentMethod>[];
};

export default function PaymentCreate({ leases, paymentMethods }: Props) {
    const { currentTeam } = usePage().props;
    const currentTeamSlug = currentTeam?.slug ?? '';

    return (
        <>
            <Head title="Plată nouă" />
            <div className="mx-auto flex w-full max-w-6xl flex-col space-y-3.5 p-3 sm:p-4">
                <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <Heading
                        variant="small"
                        title="Plată nouă"
                        description="Înregistrează o încasare pentru un contract"
                    />
                    <Button variant="outline" asChild>
                        <Link href={index(currentTeamSlug)}>
                            <ArrowLeft /> Înapoi
                        </Link>
                    </Button>
                </div>

                {leases.length === 0 ? (
                    <div className="rounded-lg border border-dashed p-4 text-sm text-muted-foreground">
                        Ai nevoie de cel puțin un contract înainte să adaugi o
                        plată.
                    </div>
                ) : null}

                <PaymentForm
                    action={{
                        action: store(currentTeamSlug).url,
                        method: 'post',
                    }}
                    submitLabel="Salvează"
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
            title: 'Plăți',
            href: props.currentTeam ? index(props.currentTeam.slug) : '/',
        },
        {
            title: 'Plată nouă',
            href: props.currentTeam ? '#' : '/',
        },
    ],
});
