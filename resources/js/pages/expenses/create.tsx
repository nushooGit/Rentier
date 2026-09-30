import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import ExpenseForm from '@/pages/expenses/form';
import { translateKey, useI18n } from '@/lib/i18n';
import { index, store } from '@/routes/expenses';
import type {
    ExpenseCategory,
    ExpenseLeaseOption,
    ExpenseOption,
    ExpensePaidBy,
    ExpensePropertyOption,
    ExpenseResponsibleParty,
    ExpenseSettlementType,
} from '@/types';

type Props = {
    properties: ExpensePropertyOption[];
    leases: ExpenseLeaseOption[];
    expenseCategories: ExpenseOption<ExpenseCategory>[];
    expensePaidByOptions: ExpenseOption<ExpensePaidBy>[];
    expenseResponsiblePartyOptions: ExpenseOption<ExpenseResponsibleParty>[];
    expenseSettlementTypeOptions: ExpenseOption<ExpenseSettlementType>[];
};

export default function ExpenseCreate({
    properties,
    leases,
    expenseCategories,
    expensePaidByOptions,
    expenseResponsiblePartyOptions,
    expenseSettlementTypeOptions,
}: Props) {
    const { currentTeam } = usePage().props;
    const { t } = useI18n();
    const currentTeamSlug = currentTeam?.slug ?? '';

    return (
        <>
            <Head title={t('expenses.create.title')} />
            <div className="mx-auto flex w-full max-w-[1180px] flex-col gap-5 p-3 sm:p-5 lg:p-6">
                <div className="flex flex-col gap-4 rounded-2xl border border-border/70 bg-card/75 p-4 shadow-sm sm:flex-row sm:items-center sm:justify-between sm:p-5">
                    <Heading
                        variant="small"
                        title={t('expenses.create.title')}
                        description={t('expenses.create.description')}
                    />
                    <Button variant="outline" asChild>
                        <Link href={index(currentTeamSlug)}>
                            <ArrowLeft /> {t('common.back')}
                        </Link>
                    </Button>
                </div>

                {properties.length === 0 ? (
                    <div className="rounded-2xl border border-dashed border-border bg-card/50 p-5 text-sm text-muted-foreground">
                        {t('expenses.emptyProperty')}
                    </div>
                ) : null}

                <ExpenseForm
                    action={{
                        action: store(currentTeamSlug).url,
                        method: 'post',
                    }}
                    submitLabel={t('common.save')}
                    properties={properties}
                    leases={leases}
                    expenseCategories={expenseCategories}
                    expensePaidByOptions={expensePaidByOptions}
                    expenseResponsiblePartyOptions={
                        expenseResponsiblePartyOptions
                    }
                    expenseSettlementTypeOptions={expenseSettlementTypeOptions}
                />
            </div>
        </>
    );
}

ExpenseCreate.layout = (props: { currentTeam?: { slug: string } | null }) => ({
    breadcrumbs: [
        {
            title: translateKey('nav.expenses'),
            href: props.currentTeam ? index(props.currentTeam.slug) : '/',
        },
        {
            title: translateKey('expenses.create.title'),
            href: props.currentTeam ? '#' : '/',
        },
    ],
});
