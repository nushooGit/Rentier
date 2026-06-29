import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import PropertyForm from '@/pages/properties/form';
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
    const currentTeamSlug = currentTeam?.slug ?? '';

    return (
        <>
            <Head title={`Editează ${property.name}`} />

            <div className="mx-auto flex w-full max-w-6xl flex-col space-y-3.5 p-3 sm:p-4">
                <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <Heading
                        variant="small"
                        title={`Editează ${property.name}`}
                        description="Actualizează detaliile, adresa și setările chiriei"
                    />
                    <Button variant="outline" asChild>
                        <Link href={show([currentTeamSlug, property.id])}>
                            <ArrowLeft /> Înapoi
                        </Link>
                    </Button>
                </div>

                <PropertyForm
                    action={{
                        action: update([currentTeamSlug, property.id]).url,
                        method: 'patch',
                    }}
                    submitLabel="Salvează"
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
            title: 'Proprietăți',
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
