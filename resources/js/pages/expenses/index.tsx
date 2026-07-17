import { Head, Link, router, usePage } from '@inertiajs/react';
import {
    CheckCircle2,
    Eye,
    Pencil,
    Plus,
    ReceiptText,
    Trash2,
} from 'lucide-react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { formatDateLong } from '@/lib/date';
import { formatMoney } from '@/lib/money';
import {
    expenseCategoryLabel,
    expensePaidByLabel,
    expenseResponsiblePartyLabel,
    expenseSettlementTypeLabel,
    expenseStatusLabel,
} from '@/pages/expenses/labels';
import { create, destroy, edit, index, show } from '@/routes/expenses';
import type {
    Expense,
    ExpenseCategory,
    ExpenseOption,
    ExpenseStatus,
    ExpenseSummary,
} from '@/types';

type Props = {
    expenses: Expense[];
    expenseCategories: ExpenseOption<ExpenseCategory>[];
    expenseStatuses: ExpenseOption<ExpenseStatus>[];
    filters: {
        category: ExpenseCategory | null;
    };
    summary: ExpenseSummary;
};

const summaryItems = [
    ['Total cheltuieli', 'total'],
    ['Suportate de proprietar', 'owner_supported'],
    ['Suportate de chiriaș', 'tenant_supported'],
    ['Plătite de proprietar', 'owner_paid'],
    ['Plătite de chiriaș', 'tenant_paid'],
] as const;

export default function ExpensesIndex({
    expenses,
    expenseCategories,
    filters,
    summary,
}: Props) {
    const { currentTeam } = usePage().props;
    const currentTeamSlug = currentTeam?.slug ?? '';
    const selectedCategory = filters.category;

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

        if (
            !window.confirm(
                `Sigur vrei să continui cu acțiunea „${expense.settlement_state.action_label}”?`,
            )
        ) {
            return;
        }

        router.patch(expense.settlement_state.action_route, undefined, {
            preserveScroll: true,
        });
    };

    const categoryHref = (category: ExpenseCategory | null) =>
        category
            ? index(currentTeamSlug, { query: { category } })
            : index(currentTeamSlug);

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

                <div className="flex gap-1.5 overflow-x-auto pb-1">
                    <Button
                        variant={
                            selectedCategory === null ? 'default' : 'outline'
                        }
                        size="sm"
                        asChild
                    >
                        <Link href={categoryHref(null)}>Toate</Link>
                    </Button>
                    {expenseCategories.map((category) => (
                        <Button
                            key={category.value}
                            variant={
                                selectedCategory === category.value
                                    ? 'default'
                                    : 'outline'
                            }
                            size="sm"
                            asChild
                        >
                            <Link href={categoryHref(category.value)}>
                                {expenseCategoryLabel(category.value)}
                            </Link>
                        </Button>
                    ))}
                </div>

                <section className="grid gap-2.5 rounded-lg border p-3 sm:p-3.5 lg:grid-cols-[1fr_1.1fr]">
                    <div className="grid gap-2 sm:grid-cols-2 xl:grid-cols-5">
                        {summaryItems.map(([label, key]) => (
                            <div key={key} className="rounded-md border p-2.5">
                                <p className="text-xs text-muted-foreground">
                                    {label}
                                </p>
                                <p className="mt-1 text-sm font-medium">
                                    {formatMoney(summary[key])}
                                </p>
                            </div>
                        ))}
                    </div>
                    <div className="grid gap-x-4 gap-y-1.5 text-sm sm:grid-cols-2 lg:border-l lg:pl-3">
                        {expenseCategories.map((category) => (
                            <div
                                key={category.value}
                                className="flex items-center justify-between gap-3"
                            >
                                <span className="text-muted-foreground">
                                    {expenseCategoryLabel(category.value)}
                                </span>
                                <span className="font-medium">
                                    {formatMoney(
                                        summary.by_category[category.value],
                                    )}
                                </span>
                            </div>
                        ))}
                    </div>
                </section>

                {expenses.length > 0 ? (
                    <div className="grid gap-2.5 md:grid-cols-2 xl:grid-cols-3">
                        {expenses.map((expense) => (
                            <article
                                key={expense.id}
                                className="flex flex-col rounded-lg border transition-colors focus-within:border-primary/30 hover:border-primary/30 hover:bg-muted/20"
                                data-test="expense-card"
                            >
                                <Link
                                    href={show([currentTeamSlug, expense.id])}
                                    className="flex flex-1 cursor-pointer flex-col gap-2.5 rounded-lg p-3 focus:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                                    data-test="expense-card-link"
                                    aria-label={`Vezi cheltuiala ${expense.title}`}
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
                                                expenseStatusLabel(
                                                    expense.status,
                                                )}
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
                                            {formatDateLong(
                                                expense.expense_date,
                                            )}{' '}
                                            ·{' '}
                                            {expensePaidByLabel(
                                                expense.paid_by,
                                            )}
                                        </span>
                                    </div>
                                    <div className="flex flex-wrap gap-1.5 text-xs">
                                        <Badge variant="outline">
                                            Plătit de:{' '}
                                            {expensePaidByLabel(
                                                expense.paid_by,
                                            )}
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
                                                expense.paid_by,
                                                expense.responsible_party,
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
                                                ? 'Afectează profitul'
                                                : 'Nu afectează profitul'}
                                        </Badge>
                                    </div>
                                    {expense.settlement_state.settled_label ? (
                                        <p className="text-xs text-muted-foreground">
                                            {
                                                expense.settlement_state
                                                    .settled_label
                                            }
                                        </p>
                                    ) : null}
                                </Link>
                                <div className="mt-auto flex justify-end gap-2 px-3 pb-3">
                                    {expense.settlement_state.action_label ? (
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            type="button"
                                            onClick={() =>
                                                settleExpense(expense)
                                            }
                                            data-test="expense-settlement-button"
                                        >
                                            <CheckCircle2 className="h-4 w-4" />
                                            {
                                                expense.settlement_state
                                                    .action_label
                                            }
                                        </Button>
                                    ) : null}
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        asChild
                                        data-test="expense-view-link"
                                    >
                                        <Link
                                            href={show([
                                                currentTeamSlug,
                                                expense.id,
                                            ])}
                                        >
                                            <Eye className="h-4 w-4" />
                                        </Link>
                                    </Button>
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        asChild
                                        data-test="expense-edit-link"
                                    >
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
                            Nu există cheltuieli
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
