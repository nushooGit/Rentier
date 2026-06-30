import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import LeaseForm from '@/pages/leases/form';
import { edit, show, update } from '@/routes/leases';
import type {
    Lease,
    LeaseOption,
    LeasePropertyOption,
    LeaseStatus,
} from '@/types';

type Props = {
    lease: Lease;
    properties: LeasePropertyOption[];
    leaseStatuses: LeaseOption<LeaseStatus>[];
};

export default function LeaseEdit({ lease, properties, leaseStatuses }: Props) {
    const { currentTeam } = usePage().props;
    const currentTeamSlug = currentTeam?.slug ?? '';

    return (
        <>
            <Head title={`Editează contract ${lease.renter.name}`} />

            <div className="mx-auto flex w-full max-w-6xl flex-col space-y-3.5 p-3 sm:p-4">
                <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <Heading
                        variant="small"
                        title="Editează contract"
                        description={`${lease.renter.name} · ${lease.property.name}`}
                    />
                    <Button variant="outline" asChild>
                        <Link href={show([currentTeamSlug, lease.id])}>
                            <ArrowLeft /> Înapoi
                        </Link>
                    </Button>
                </div>

                <LeaseForm
                    action={{
                        action: update([currentTeamSlug, lease.id]).url,
                        method: 'patch',
                    }}
                    submitLabel="Salvează"
                    lease={lease}
                    properties={properties}
                    leaseStatuses={leaseStatuses}
                />
            </div>
        </>
    );
}

LeaseEdit.layout = (props: {
    currentTeam?: { slug: string } | null;
    lease: Lease;
}) => ({
    breadcrumbs: [
        {
            title: 'Contracte',
            href: props.currentTeam
                ? show([props.currentTeam.slug, props.lease.id])
                : '/',
        },
        {
            title: props.lease.renter.name,
            href: props.currentTeam
                ? edit([props.currentTeam.slug, props.lease.id])
                : '/',
        },
    ],
});
