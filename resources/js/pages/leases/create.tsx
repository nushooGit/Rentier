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

            <div className="mx-auto flex w-full max-w-[1180px] flex-col gap-5 p-3 sm:p-5 lg:p-6">
                <div className="flex flex-col gap-4 rounded-2xl border border-border/70 bg-card/75 p-4 shadow-sm sm:flex-row sm:items-center sm:justify-between sm:p-5">
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
                    <div className="rounded-2xl border border-dashed border-border bg-card/50 p-5 text-sm text-muted-foreground">
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
