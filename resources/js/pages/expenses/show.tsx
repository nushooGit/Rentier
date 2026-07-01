import { Head, Link, router, usePage } from '@inertiajs/react';
import { ArrowLeft, Pencil, Trash2 } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { formatDateLong } from '@/lib/date';
import {
    expenseCategoryLabel,
    expensePaidByLabel,
    expenseStatusLabel,
} from '@/pages/expenses/labels';
import { destroy, edit, index, show } from '@/routes/expenses';
import type { Expense } from '@/types';

type Props = {
    expense: Expense;
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

function Detail({
    label,
    value,
}: {
    label: string;
    value?: string | number | null;
}) {
    return (
        <div>
            <dt className="text-sm text-muted-foreground">{label}</dt>
            <dd className="mt-1 text-sm font-medium">{value ?? 'Nesetat'}</dd>
        </div>
    );
}

export default function ExpenseShow({ expense }: Props) {
    const { currentTeam } = usePage().props;
    const currentTeamSlug = currentTeam?.slug ?? '';

    const deleteExpense = () => {
        if (!window.confirm(`Ștergi ${expense.title}?`)) {
            return;
        }

        router.delete(destroy([currentTeamSlug, expense.id]).url);
    };

    return (
        <>
            <Head title={expense.title} />
            <div className="mx-auto flex w-full max-w-6xl flex-col space-y-3.5 p-3 sm:p-4">
                <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div className="space-y-2">
                        <p className="text-sm text-muted-foreground">
                            Sumă cheltuită
                        </p>
                        <div className="flex flex-wrap items-center gap-2">
                            <h1 className="text-2xl font-semibold tracking-normal">
                                {formatMoney(expense.amount, expense.currency)}
                            </h1>
                            <Badge variant="secondary">
                                {expenseStatusLabel(expense.status)}
                            </Badge>
                        </div>
                        <p className="text-sm text-muted-foreground">
                            {expense.title} · {expense.property.name}
                        </p>
                    </div>
                    <div className="flex flex-col-reverse gap-2 sm:flex-row">
                        <Button variant="outline" asChild>
                            <Link href={index(currentTeamSlug)}>
                                <ArrowLeft /> Înapoi
                            </Link>
                        </Button>
                        <Button asChild>
                            <Link href={edit([currentTeamSlug, expense.id])}>
                                <Pencil /> Editează
                            </Link>
                        </Button>
                        <Button variant="destructive" onClick={deleteExpense}>
                            <Trash2 /> Șterge
                        </Button>
                    </div>
                </div>

                <section className="rounded-lg border p-3 sm:p-3.5">
                    <h2 className="text-base font-medium">
                        Detalii cheltuială
                    </h2>
                    <dl className="mt-2.5 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                        <Detail
                            label="Proprietate"
                            value={expense.property.name}
                        />
                        <Detail
                            label="Contract"
                            value={
                                expense.lease
                                    ? `${expense.property.name} · ${expense.lease.renter.name}`
                                    : null
                            }
                        />
                        <Detail
                            label="Categorie"
                            value={expenseCategoryLabel(expense.category)}
                        />
                        <Detail
                            label="Plătitor"
                            value={expensePaidByLabel(expense.paid_by)}
                        />
                        <Detail
                            label="Data cheltuielii"
                            value={formatDateLong(expense.expense_date)}
                        />
                        <Detail
                            label="Status"
                            value={expenseStatusLabel(expense.status)}
                        />
                    </dl>
                </section>

                {expense.notes ? (
                    <section className="rounded-lg border p-3 sm:p-3.5">
                        <h2 className="text-base font-medium">Note</h2>
                        <p className="mt-2.5 text-sm whitespace-pre-wrap">
                            {expense.notes}
                        </p>
                    </section>
                ) : null}
            </div>
        </>
    );
}

ExpenseShow.layout = (props: {
    currentTeam?: { slug: string } | null;
    expense: Expense;
}) => ({
    breadcrumbs: [
        {
            title: 'Cheltuieli',
            href: props.currentTeam ? index(props.currentTeam.slug) : '/',
        },
        {
            title: props.expense.title,
            href: props.currentTeam
                ? show([props.currentTeam.slug, props.expense.id])
                : '/',
        },
    ],
});
