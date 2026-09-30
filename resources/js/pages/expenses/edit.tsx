import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import ExpenseForm from '@/pages/expenses/form';
import { show, update } from '@/routes/expenses';
import type {
    Expense,
    ExpenseCategory,
    ExpenseLeaseOption,
    ExpenseOption,
    ExpensePaidBy,
    ExpensePropertyOption,
    ExpenseResponsibleParty,
    ExpenseSettlementType,
} from '@/types';

type Props = {
    expense: Expense;
    properties: ExpensePropertyOption[];
    leases: ExpenseLeaseOption[];
    expenseCategories: ExpenseOption<ExpenseCategory>[];
    expensePaidByOptions: ExpenseOption<ExpensePaidBy>[];
    expenseResponsiblePartyOptions: ExpenseOption<ExpenseResponsibleParty>[];
    expenseSettlementTypeOptions: ExpenseOption<ExpenseSettlementType>[];
};

export default function ExpenseEdit({
    expense,
    properties,
    leases,
    expenseCategories,
    expensePaidByOptions,
    expenseResponsiblePartyOptions,
    expenseSettlementTypeOptions,
}: Props) {
    const { currentTeam } = usePage().props;
    const currentTeamSlug = currentTeam?.slug ?? '';

    return (
        <>
            <Head title={`Editează ${expense.title}`} />
            <div className="mx-auto flex w-full max-w-[1180px] flex-col gap-5 p-3 sm:p-5 lg:p-6">
                <div className="flex flex-col gap-4 rounded-2xl border border-border/70 bg-card/75 p-4 shadow-sm sm:flex-row sm:items-center sm:justify-between sm:p-5">
                    <Heading
                        variant="small"
                        title="Editează costul"
                        description={`${expense.title} · ${expense.property.name}`}
                    />
                    <Button variant="outline" asChild>
                        <Link href={show([currentTeamSlug, expense.id])}>
                            <ArrowLeft /> Înapoi
                        </Link>
                    </Button>
                </div>

                <ExpenseForm
                    action={{
                        action: update([currentTeamSlug, expense.id]).url,
                        method: 'patch',
                    }}
                    submitLabel="Salvează"
                    expense={expense}
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
