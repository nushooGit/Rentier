import type { ExpenseCategory, ExpensePaidBy, ExpenseStatus } from '@/types';

export const expenseCategoryLabels: Record<ExpenseCategory, string> = {
    maintenance: 'Întreținere',
    utilities: 'Utilități',
    taxes: 'Taxe',
    insurance: 'Asigurare',
    admin: 'Administrare',
    repairs: 'Reparații',
    other: 'Altă categorie',
};

export const expensePaidByLabels: Record<ExpensePaidBy, string> = {
    landlord: 'Proprietar',
    renter: 'Chiriaș',
    other: 'Altul',
};

export const expenseStatusLabels: Record<ExpenseStatus, string> = {
    paid: 'Plătită',
    pending: 'În așteptare',
    reimbursable: 'De recuperat',
    cancelled: 'Anulată',
};

export function expenseCategoryLabel(value: ExpenseCategory) {
    return expenseCategoryLabels[value] ?? value;
}

export function expensePaidByLabel(value: ExpensePaidBy) {
    return expensePaidByLabels[value] ?? value;
}

export function expenseStatusLabel(value: ExpenseStatus) {
    return expenseStatusLabels[value] ?? value;
}
