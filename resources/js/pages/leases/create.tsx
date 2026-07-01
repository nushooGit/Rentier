import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import LeaseForm from '@/pages/leases/form';
import { index, store } from '@/routes/leases';
import type { LeasePropertyOption } from '@/types';

type Props = {
    properties: LeasePropertyOption[];
};

export default function LeaseCreate({ properties }: Props) {
    const { currentTeam } = usePage().props;
    const currentTeamSlug = currentTeam?.slug ?? '';

    return (
        <>
            <Head title="Contract nou" />

            <div className="mx-auto flex w-full max-w-6xl flex-col space-y-3.5 p-3 sm:p-4">
                <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <Heading
                        variant="small"
                        title="Contract nou"
                        description="Adaugă proprietatea, chiriașul și setările chiriei"
                    />
                    <Button variant="outline" asChild>
                        <Link href={index(currentTeamSlug)}>
                            <ArrowLeft /> Înapoi
                        </Link>
                    </Button>
                </div>

                {properties.length === 0 ? (
                    <div className="rounded-lg border border-dashed p-4 text-sm text-muted-foreground">
                        Ai nevoie de cel puțin o proprietate în acest workspace
                        înainte să creezi un contract.
                    </div>
                ) : null}

                <LeaseForm
                    action={{
                        action: store(currentTeamSlug).url,
                        method: 'post',
                    }}
                    submitLabel="Creează contract"
                    properties={properties}
                />
            </div>
        </>
    );
}

LeaseCreate.layout = (props: { currentTeam?: { slug: string } | null }) => ({
    breadcrumbs: [
        {
            title: 'Contracte',
            href: props.currentTeam ? index(props.currentTeam.slug) : '/',
        },
        {
            title: 'Contract nou',
            href: props.currentTeam ? '#' : '/',
        },
    ],
});
