import type {
    ExpenseCategory,
    ExpensePaidBy,
    ExpenseResponsibleParty,
    ExpenseSettlementType,
    ExpenseStatus,
} from '@/types';

export const expenseCategoryLabels: Record<ExpenseCategory, string> = {
    maintenance: 'Întreținere',
    utilities: 'Utilități',
    taxes: 'Taxe',
    insurance: 'Asigurare',
    admin: 'Administrare',
    repairs: 'Reparații',
    other: 'Altă categorie',
};

export const expensePaidByLabels: Record<string, string> = {
    owner: 'Proprietar',
    tenant: 'Chirias',
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

export const expenseResponsiblePartyLabels: Record<
    ExpenseResponsibleParty,
    string
> = {
    owner: 'Proprietar',
    tenant: 'Chirias',
};

export const expenseSettlementTypeLabels: Record<
    ExpenseSettlementType,
    string
> = {
    none: 'Nu se deconteaza',
    deduct_from_rent: 'Se scade din chirie',
    deduct_from_utilities: 'Se scade din utilitati',
    reimburse: 'Se ramburseaza separat',
};

export function expenseCategoryLabel(value: ExpenseCategory) {
    return expenseCategoryLabels[value] ?? value;
}

export function expensePaidByLabel(value: ExpensePaidBy) {
    return expensePaidByLabels[value] ?? value;
}

export function expenseResponsiblePartyLabel(value: ExpenseResponsibleParty) {
    return expenseResponsiblePartyLabels[value] ?? value;
}

export function expenseSettlementTypeLabel(value: ExpenseSettlementType) {
    return expenseSettlementTypeLabels[value] ?? value;
}

export function expenseStatusLabel(value: ExpenseStatus) {
    return expenseStatusLabels[value] ?? value;
}
