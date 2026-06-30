import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import ExpenseForm from '@/pages/expenses/form';
import { index, store } from '@/routes/expenses';
import type {
    ExpenseCategory,
    ExpenseLeaseOption,
    ExpenseOption,
    ExpensePaidBy,
    ExpensePropertyOption,
    ExpenseStatus,
} from '@/types';

type Props = {
    properties: ExpensePropertyOption[];
    leases: ExpenseLeaseOption[];
    expenseCategories: ExpenseOption<ExpenseCategory>[];
    expensePaidByOptions: ExpenseOption<ExpensePaidBy>[];
    expenseStatuses: ExpenseOption<ExpenseStatus>[];
};

export default function ExpenseCreate({
    properties,
    leases,
    expenseCategories,
    expensePaidByOptions,
    expenseStatuses,
}: Props) {
    const { currentTeam } = usePage().props;
    const currentTeamSlug = currentTeam?.slug ?? '';

    return (
        <>
            <Head title="Cheltuială nouă" />
            <div className="mx-auto flex w-full max-w-6xl flex-col space-y-3.5 p-3 sm:p-4">
                <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <Heading
                        variant="small"
                        title="Cheltuială nouă"
                        description="Adaugă un cost pentru o proprietate"
                    />
                    <Button variant="outline" asChild>
                        <Link href={index(currentTeamSlug)}>
                            <ArrowLeft /> Înapoi
                        </Link>
                    </Button>
                </div>

                {properties.length === 0 ? (
                    <div className="rounded-lg border border-dashed p-4 text-sm text-muted-foreground">
                        Ai nevoie de cel puțin o proprietate înainte să adaugi o
                        cheltuială.
                    </div>
                ) : null}

                <ExpenseForm
                    action={{
                        action: store(currentTeamSlug).url,
                        method: 'post',
                    }}
                    submitLabel="Salvează"
                    properties={properties}
                    leases={leases}
                    expenseCategories={expenseCategories}
                    expensePaidByOptions={expensePaidByOptions}
                    expenseStatuses={expenseStatuses}
                />
            </div>
        </>
    );
}

ExpenseCreate.layout = (props: { currentTeam?: { slug: string } | null }) => ({
    breadcrumbs: [
        {
            title: 'Cheltuieli',
            href: props.currentTeam ? index(props.currentTeam.slug) : '/',
        },
        {
            title: 'Cheltuială nouă',
            href: props.currentTeam ? '#' : '/',
        },
    ],
});
