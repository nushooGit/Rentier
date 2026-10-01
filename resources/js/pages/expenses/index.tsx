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
import { translateKey, useI18n } from '@/lib/i18n';
import { formatMoney } from '@/lib/money';
import {
    expenseCategoryLabel,
    expensePaidByLabel,
    expenseResponsiblePartyLabel,
    expenseSettlementActionLabel,
    expenseSettlementSettledLabel,
    expenseSettlementStateLabel,
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
    ['expenses.index.total', 'total'],
    ['expenses.index.ownerSupported', 'owner_supported'],
    ['expenses.index.renterSupported', 'tenant_supported'],
    ['expenses.index.ownerPaid', 'owner_paid'],
    ['expenses.index.renterPaid', 'tenant_paid'],
] as const;

export default function ExpensesIndex({
    expenses,
    expenseCategories,
    filters,
    summary,
}: Props) {
    const { currentTeam } = usePage().props;
    const { t } = useI18n();
    const currentTeamSlug = currentTeam?.slug ?? '';
    const selectedCategory = filters.category;

    const deleteExpense = (expense: Expense) => {
        if (!window.confirm(t('expenses.index.deleteConfirm'))) {
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
                t('expenses.index.actionConfirm', {
                    action: expenseSettlementActionLabel(expense.settlement_state.kind) ?? '',
                }),
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
            <Head title={t('expenses.index.title')} />
            <div className="mx-auto flex w-full max-w-[1480px] flex-col gap-5 p-3 sm:p-5 lg:p-6">
                <div className="flex flex-col gap-4 rounded-2xl border border-border/70 bg-card/75 p-4 shadow-sm sm:flex-row sm:items-center sm:justify-between sm:p-5">
                    <Heading
                        variant="small"
                        title={t('expenses.index.title')}
                        description={t('expenses.index.description')}
                    />
                    <Button asChild data-test="expense-create-link">
                        <Link href={create(currentTeamSlug)}>
                            <Plus /> {t('expenses.index.new')}
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
                        <Link href={categoryHref(null)}>{t('common.all')}</Link>
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

                <section className="grid gap-3 rounded-2xl border border-border/70 bg-card/80 p-4 shadow-sm lg:grid-cols-[1fr_1.1fr]">
                    <div className="grid gap-2 sm:grid-cols-2 xl:grid-cols-5">
                        {summaryItems.map(([label, key]) => (
                            <div key={key} className="rounded-xl border border-border/70 bg-background/65 p-3">
                                <p className="text-xs text-muted-foreground">
                                    {t(label)}
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
                    <div className="grid gap-3.5 md:grid-cols-2 xl:grid-cols-3">
                        {expenses.map((expense) => (
                            <article
                                key={expense.id}
                                className="flex flex-col rounded-2xl border border-border/70 bg-card/90 shadow-sm transition-all focus-within:border-primary/35 hover:-translate-y-0.5 hover:border-primary/30 hover:shadow-md"
                                data-test="expense-card"
                            >
                                <Link
                                    href={show([currentTeamSlug, expense.id])}
                                    className="flex flex-1 cursor-pointer flex-col gap-3 rounded-2xl p-4 focus:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                                    data-test="expense-card-link"
                                    aria-label={t('expenses.index.view', { name: expense.title })}
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
                                            {expenseSettlementStateLabel(
                                                expense.settlement_state.kind,
                                            ) ??
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
                                            {t('expenses.field.paidByPrefix')}:{' '}
                                            {expensePaidByLabel(
                                                expense.paid_by,
                                            )}
                                        </Badge>
                                        <Badge variant="outline">
                                            {t('expenses.field.responsiblePrefix')}:{' '}
                                            {expenseResponsiblePartyLabel(
                                                expense.responsible_party,
                                            )}
                                        </Badge>
                                        <Badge variant="outline">
                                            {t('expenses.field.settlementPrefix')}:{' '}
                                            {expenseSettlementTypeLabel(
                                                expense.settlement_type,
                                                expense.paid_by,
                                                expense.responsible_party,
                                            )}
                                        </Badge>
                                        {expenseSettlementStateLabel(
                                            expense.settlement_state.kind,
                                        ) ? (
                                            <Badge
                                                variant={
                                                    expense.settled_at
                                                        ? 'secondary'
                                                        : 'outline'
                                                }
                                            >
                                                {expenseSettlementStateLabel(
                                                    expense.settlement_state.kind,
                                                )}
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
                                                ? t('expenses.profit.affects')
                                                : t('expenses.profit.notAffects')}
                                        </Badge>
                                    </div>
                                    {expenseSettlementSettledLabel(
                                        expense.settlement_state.kind,
                                        expense.settled_at,
                                    ) ? (
                                        <p className="text-xs text-muted-foreground">
                                            {expenseSettlementSettledLabel(
                                                expense.settlement_state.kind,
                                                expense.settled_at,
                                            )}
                                        </p>
                                    ) : null}
                                </Link>
                                <div className="mt-auto flex flex-wrap justify-end gap-1.5 border-t border-border/60 bg-muted/20 px-3 py-2.5">
                                    {expenseSettlementActionLabel(
                                        expense.settlement_state.kind,
                                    ) ? (
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
                                            {expenseSettlementActionLabel(
                                                expense.settlement_state.kind,
                                            )}
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
                                        aria-label={t('expenses.index.delete')}
                                        data-test="expense-delete-button"
                                    >
                                        <Trash2 className="h-4 w-4" />
                                    </Button>
                                </div>
                            </article>
                        ))}
                    </div>
                ) : (
                    <div className="rounded-2xl border border-dashed border-border bg-card/50 p-8 text-center shadow-sm">
                        <ReceiptText className="mx-auto h-8 w-8 text-muted-foreground" />
                        <h2 className="mt-3 text-base font-medium">
                            {t('expenses.index.emptyTitle')}
                        </h2>
                        <p className="mt-1 text-sm text-muted-foreground">
                            {t('expenses.index.emptyDescription')}
                        </p>
                        <Button className="mt-4" asChild>
                            <Link href={create(currentTeamSlug)}>
                                <Plus /> {t('expenses.index.new')}
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
            title: translateKey('expenses.index.title'),
            href: props.currentTeam ? index(props.currentTeam.slug) : '/',
        },
    ],
});
