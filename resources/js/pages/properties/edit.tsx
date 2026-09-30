import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import PropertyForm from '@/pages/properties/form';
import { translateKey, useI18n } from '@/lib/i18n';
import { edit, show, update } from '@/routes/properties';
import type {
    Property,
    PropertyOption,
    PropertyStatus,
    PropertyType,
} from '@/types';

type Props = {
    property: Property;
    propertyTypes: PropertyOption<PropertyType>[];
    propertyStatuses: PropertyOption<PropertyStatus>[];
};

export default function PropertyEdit({
    property,
    propertyTypes,
    propertyStatuses,
}: Props) {
    const { currentTeam } = usePage().props;
    const { t } = useI18n();
    const currentTeamSlug = currentTeam?.slug ?? '';

    return (
        <>
            <Head title={`${t('properties.edit.title')}: ${property.name}`} />

            <div className="mx-auto flex w-full max-w-[1180px] flex-col gap-5 p-3 sm:p-5 lg:p-6">
                <div className="flex flex-col gap-4 rounded-2xl border border-border/70 bg-card/75 p-4 shadow-sm sm:flex-row sm:items-center sm:justify-between sm:p-5">
                    <Heading
                        variant="small"
                        title={`${t('properties.edit.title')}: ${property.name}`}
                        description={t('properties.edit.description')}
                    />
                    <Button variant="outline" asChild>
                        <Link href={show([currentTeamSlug, property.id])}>
                            <ArrowLeft /> {t('common.back')}
                        </Link>
                    </Button>
                </div>

                <PropertyForm
                    action={{
                        action: update([currentTeamSlug, property.id]).url,
                        method: 'patch',
                    }}
                    submitLabel={t('common.save')}
                    property={property}
                    propertyTypes={propertyTypes}
                    propertyStatuses={propertyStatuses}
                />
            </div>
        </>
    );
}

PropertyEdit.layout = (props: {
    currentTeam?: { slug: string } | null;
    property: Property;
}) => ({
    breadcrumbs: [
        {
            title: translateKey('nav.properties'),
            href: props.currentTeam
                ? show([props.currentTeam.slug, props.property.id])
                : '/',
        },
        {
            title: props.property.name,
            href: props.currentTeam
                ? edit([props.currentTeam.slug, props.property.id])
                : '/',
        },
    ],
});
