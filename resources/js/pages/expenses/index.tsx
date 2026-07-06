import { Head, Link, router, usePage } from '@inertiajs/react';
import { CheckCircle2, Eye, Pencil, Plus, ReceiptText, Trash2 } from 'lucide-react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { formatDateLong } from '@/lib/date';
import {
    expenseCategoryLabel,
    expensePaidByLabel,
    expenseResponsiblePartyLabel,
    expenseSettlementTypeLabel,
    expenseStatusLabel,
} from '@/pages/expenses/labels';
import { create, destroy, edit, index, show } from '@/routes/expenses';
import type { Expense, ExpenseOption, ExpenseStatus } from '@/types';

type Props = {
    expenses: Expense[];
    expenseStatuses: ExpenseOption<ExpenseStatus>[];
};

function formatMoney(amount?: string | null, currency = 'RON') {
    if (!amount) {
        return 'Nesetat';
    }

    return `${Number(amount).toLocaleString('ro-RO', {
        maximumFractionDigits: 2,
        minimumFractionDigits: 0,
    })} ${currency}`;
}

export default function ExpensesIndex({ expenses }: Props) {
    const { currentTeam } = usePage().props;
    const currentTeamSlug = currentTeam?.slug ?? '';

    const deleteExpense = (expense: Expense) => {
        if (
            !window.confirm(
                'Sigur vrei să ștergi această cheltuială? Acțiunea nu poate fi anulată.',
            )
        ) {
            return;
        }

        router.delete(destroy([currentTeamSlug, expense.id]).url);
    };

    const settleExpense = (expense: Expense) => {
        if (!expense.settlement_state.action_route) {
            return;
        }

        router.patch(expense.settlement_state.action_route, undefined, {
            preserveScroll: true,
        });
    };

    return (
        <>
            <Head title="Cheltuieli" />
            <div className="mx-auto flex w-full max-w-7xl flex-col space-y-3.5 p-3 sm:p-4">
                <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <Heading
                        variant="small"
                        title="Cheltuieli"
                        description="Urmărește costurile pe proprietăți și contracte"
                    />
                    <Button asChild data-test="expense-create-link">
                        <Link href={create(currentTeamSlug)}>
                            <Plus /> Cheltuială nouă
                        </Link>
                    </Button>
                </div>

                {expenses.length > 0 ? (
                    <div className="grid gap-2.5 md:grid-cols-2 xl:grid-cols-3">
                        {expenses.map((expense) => (
                            <article
                                key={expense.id}
                                className="flex flex-col gap-2.5 rounded-lg border p-3"
                                data-test="expense-card"
                            >
                                <div className="flex items-start justify-between gap-3">
                                    <div className="min-w-0">
                                        <h2 className="truncate text-base font-medium">
                                            {expense.title}
                                        </h2>
                                        <p className="mt-1 text-sm text-muted-foreground">
                                            {expense.property.name} ·{' '}
                                            {expenseCategoryLabel(
                                                expense.category,
                                            )}
                                        </p>
                                    </div>
                                    <Badge variant="secondary">
                                        {expense.settlement_state.label ??
                                            expenseStatusLabel(expense.status)}
                                    </Badge>
                                </div>
                                <div className="grid gap-0.5 text-sm">
                                    <span className="font-medium">
                                        {formatMoney(
                                            expense.amount,
                                            expense.currency,
                                        )}
                                    </span>
                                    <span className="text-muted-foreground">
                                        {formatDateLong(expense.expense_date)} ·{' '}
                                        {expensePaidByLabel(expense.paid_by)}
                                    </span>
                                </div>
                                <div className="flex flex-wrap gap-1.5 text-xs">
                                    <Badge variant="outline">
                                        Platit de:{' '}
                                        {expensePaidByLabel(expense.paid_by)}
                                    </Badge>
                                    <Badge variant="outline">
                                        Suportat de:{' '}
                                        {expenseResponsiblePartyLabel(
                                            expense.responsible_party,
                                        )}
                                    </Badge>
                                    <Badge variant="outline">
                                        Decontare:{' '}
                                        {expenseSettlementTypeLabel(
                                            expense.settlement_type,
                                        )}
                                    </Badge>
                                    {expense.settlement_state.label ? (
                                        <Badge
                                            variant={
                                                expense.settled_at
                                                    ? 'secondary'
                                                    : 'outline'
                                            }
                                        >
                                            {expense.settlement_state.label}
                                        </Badge>
                                    ) : null}
                                    <Badge
                                        variant={
                                            expense.affects_owner_profit
                                                ? 'secondary'
                                                : 'outline'
                                        }
                                    >
                                        {expense.affects_owner_profit
                                            ? 'Afecteaza profitul'
                                            : 'Nu afecteaza profitul'}
                                    </Badge>
                                </div>
                                {expense.settlement_state.settled_label ? (
                                    <p className="text-xs text-muted-foreground">
                                        {expense.settlement_state.settled_label}
                                    </p>
                                ) : null}
                                <div className="mt-auto flex justify-end gap-2">
                                    {expense.settlement_state.action_label ? (
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            type="button"
                                            onClick={() =>
                                                settleExpense(expense)
                                            }
                                        >
                                            <CheckCircle2 className="h-4 w-4" />
                                            {expense.settlement_state.action_label}
                                        </Button>
                                    ) : null}
                                    <Button variant="ghost" size="sm" asChild>
                                        <Link
                                            href={show([
                                                currentTeamSlug,
                                                expense.id,
                                            ])}
                                        >
                                            <Eye className="h-4 w-4" />
                                        </Link>
                                    </Button>
                                    <Button variant="ghost" size="sm" asChild>
                                        <Link
                                            href={edit([
                                                currentTeamSlug,
                                                expense.id,
                                            ])}
                                        >
                                            <Pencil className="h-4 w-4" />
                                        </Link>
                                    </Button>
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        type="button"
                                        onClick={() => deleteExpense(expense)}
                                        aria-label="Șterge cheltuiala"
                                        data-test="expense-delete-button"
                                    >
                                        <Trash2 className="h-4 w-4" />
                                    </Button>
                                </div>
                            </article>
                        ))}
                    </div>
                ) : (
                    <div className="rounded-lg border border-dashed p-5 text-center sm:p-6">
                        <ReceiptText className="mx-auto h-8 w-8 text-muted-foreground" />
                        <h2 className="mt-3 text-base font-medium">
                            Nu există cheltuieli încă
                        </h2>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Adaugă prima cheltuială pentru o proprietate.
                        </p>
                        <Button className="mt-4" asChild>
                            <Link href={create(currentTeamSlug)}>
                                <Plus /> Cheltuială nouă
                            </Link>
                        </Button>
                    </div>
                )}
            </div>
        </>
    );
}

ExpensesIndex.layout = (props: { currentTeam?: { slug: string } | null }) => ({
    breadcrumbs: [
        {
            title: 'Cheltuieli',
            href: props.currentTeam ? index(props.currentTeam.slug) : '/',
        },
    ],
});
