import { Head } from '@inertiajs/react';
import AppearanceTabs from '@/components/appearance-tabs';
import Heading from '@/components/heading';
import { edit as editAppearance } from '@/routes/appearance';

export default function Appearance() {
    return (
        <>
            <Head title="Aspect" />

            <h1 className="sr-only">Setări de aspect</h1>

            <div className="space-y-6">
                <Heading
                    variant="small"
                    title="Aspect"
                    description="Alege tema luminoasă, întunecată sau tema sistemului."
                />
                <AppearanceTabs />
            </div>
        </>
    );
}

Appearance.layout = {
    breadcrumbs: [
        {
            title: 'Aspect',
            href: editAppearance(),
        },
    ],
};
