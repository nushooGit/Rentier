import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import PropertyForm from '@/pages/properties/form';
import { translateKey, useI18n } from '@/lib/i18n';
import { index, store } from '@/routes/properties';
import type { PropertyOption, PropertyStatus, PropertyType } from '@/types';

type Props = {
    propertyTypes: PropertyOption<PropertyType>[];
    propertyStatuses: PropertyOption<PropertyStatus>[];
};

export default function PropertyCreate({
    propertyTypes,
    propertyStatuses,
}: Props) {
    const { currentTeam } = usePage().props;
    const { t } = useI18n();
    const currentTeamSlug = currentTeam?.slug ?? '';

    return (
        <>
            <Head title={t('properties.create.title')} />

            <div className="mx-auto flex w-full max-w-[1180px] flex-col gap-5 p-3 sm:p-5 lg:p-6">
                <div className="flex flex-col gap-4 rounded-2xl border border-border/70 bg-card/75 p-4 shadow-sm sm:flex-row sm:items-center sm:justify-between sm:p-5">
                    <Heading
                        variant="small"
                        title={t('properties.create.title')}
                        description={t('properties.create.description')}
                    />
                    <Button variant="outline" asChild>
                        <Link href={index(currentTeamSlug)}>
                            <ArrowLeft /> {t('common.back')}
                        </Link>
                    </Button>
                </div>

                <PropertyForm
                    action={{
                        action: store(currentTeamSlug).url,
                        method: 'post',
                    }}
                    submitLabel={t('common.save')}
                    propertyTypes={propertyTypes}
                    propertyStatuses={propertyStatuses}
                />
            </div>
        </>
    );
}

PropertyCreate.layout = (props: { currentTeam?: { slug: string } | null }) => ({
    breadcrumbs: [
        {
            title: translateKey('nav.properties'),
            href: props.currentTeam ? index(props.currentTeam.slug) : '/',
        },
        {
            title: translateKey('properties.create.title'),
            href: props.currentTeam ? '#' : '/',
        },
    ],
});
