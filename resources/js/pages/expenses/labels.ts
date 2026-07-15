import type {
    ExpenseCategory,
    ExpensePaidBy,
    ExpenseResponsibleParty,
    ExpenseSettlementType,
    ExpenseStatus,
} from '@/types';

export const expenseCategoryLabels: Record<ExpenseCategory, string> = {
    repairs: 'Reparații',
    maintenance: 'Întreținere',
    utilities: 'Utilități',
    renovation: 'Zugrăvit / renovări',
    taxes: 'Taxe',
    other: 'Altele',
};

export const expensePaidByLabels: Record<string, string> = {
    owner: 'Proprietar',
    tenant: 'Chiriaș',
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
    tenant: 'Chiriaș',
};

export const expenseSettlementTypeLabels: Record<
    ExpenseSettlementType,
    string
> = {
    none: 'Nu se decontează',
    deduct_from_rent: 'Se scade din chirie',
    deduct_from_utilities: 'Se scade din utilități',
    reimburse: 'Se rambursează separat',
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
