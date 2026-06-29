import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import PropertyForm from '@/pages/properties/form';
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
    const currentTeamSlug = currentTeam?.slug ?? '';

    return (
        <>
            <Head title="Proprietate nouă" />

            <div className="mx-auto flex w-full max-w-6xl flex-col space-y-3.5 p-3 sm:p-4">
                <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <Heading
                        variant="small"
                        title="Proprietate nouă"
                        description="Adaugă detaliile principale ale proprietății"
                    />
                    <Button variant="outline" asChild>
                        <Link href={index(currentTeamSlug)}>
                            <ArrowLeft /> Înapoi
                        </Link>
                    </Button>
                </div>

                <PropertyForm
                    action={{
                        action: store(currentTeamSlug).url,
                        method: 'post',
                    }}
                    submitLabel="Salvează"
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
            title: 'Proprietăți',
            href: props.currentTeam ? index(props.currentTeam.slug) : '/',
        },
        {
            title: 'Proprietate nouă',
            href: props.currentTeam ? '#' : '/',
        },
    ],
});
