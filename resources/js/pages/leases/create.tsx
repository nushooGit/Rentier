import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import LeaseForm from '@/pages/leases/form';
import { translateKey, useI18n } from '@/lib/i18n';
import { index, store } from '@/routes/leases';
import type { LeasePropertyOption } from '@/types';

type Props = {
    properties: LeasePropertyOption[];
};

export default function LeaseCreate({ properties }: Props) {
    const { currentTeam } = usePage().props;
    const { t } = useI18n();
    const currentTeamSlug = currentTeam?.slug ?? '';

    return (
        <>
            <Head title={t('leases.create.title')} />

            <div className="mx-auto flex w-full max-w-[1180px] flex-col gap-5 p-3 sm:p-5 lg:p-6">
                <div className="flex flex-col gap-4 rounded-2xl border border-border/70 bg-card/75 p-4 shadow-sm sm:flex-row sm:items-center sm:justify-between sm:p-5">
                    <Heading
                        variant="small"
                        title={t('leases.create.title')}
                        description={t('leases.create.description')}
                    />
                    <Button variant="outline" asChild>
                        <Link href={index(currentTeamSlug)}>
                            <ArrowLeft /> {t('common.back')}
                        </Link>
                    </Button>
                </div>

                {properties.length === 0 ? (
                    <div className="rounded-2xl border border-dashed border-border bg-card/50 p-5 text-sm text-muted-foreground">
                        {t('leases.emptyProperty')}
                    </div>
                ) : null}

                <LeaseForm
                    action={{
                        action: store(currentTeamSlug).url,
                        method: 'post',
                    }}
                    submitLabel={t('leases.create.submit')}
                    properties={properties}
                />
            </div>
        </>
    );
}

LeaseCreate.layout = (props: { currentTeam?: { slug: string } | null }) => ({
    breadcrumbs: [
        {
            title: translateKey('nav.leases'),
            href: props.currentTeam ? index(props.currentTeam.slug) : '/',
        },
        {
            title: translateKey('leases.create.title'),
            href: props.currentTeam ? '#' : '/',
        },
    ],
});
