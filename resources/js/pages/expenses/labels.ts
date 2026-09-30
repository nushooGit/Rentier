import { formatDateLong } from '@/lib/date';
import { currentAppLocale } from '@/lib/locale';
import type {
    ExpenseCategory,
    ExpensePaidBy,
    ExpenseResponsibleParty,
    ExpenseSettlementType,
    ExpenseStatus,
} from '@/types';

export type ExpenseSettlementKind =
    | 'none'
    | 'reimbursement_due'
    | 'reimbursed'
    | 'recovery_due'
    | 'recovered';

export const expenseCategoryLabels: Record<ExpenseCategory, string> = {
    repairs: 'Reparații',
    maintenance: 'Întreținere',
    utilities: 'Utilități',
    renovation: 'Zugrăvit / renovări',
    taxes: 'Taxe',
    other: 'Altele',
};

const expenseCategoryLabelsEn: Record<ExpenseCategory, string> = {
    repairs: 'Repairs',
    maintenance: 'Maintenance',
    utilities: 'Utilities',
    renovation: 'Painting / renovation',
    taxes: 'Taxes',
    other: 'Other',
};

export const expensePaidByLabels: Record<string, string> = {
    owner: 'Proprietar',
    tenant: 'Chiriaș',
    landlord: 'Proprietar',
    renter: 'Chiriaș',
    other: 'Altul',
};

const expensePaidByLabelsEn: Record<string, string> = {
    owner: 'Owner',
    tenant: 'Renter',
    landlord: 'Owner',
    renter: 'Renter',
    other: 'Other',
};

export const expenseStatusLabels: Record<ExpenseStatus, string> = {
    paid: 'Plătită',
    pending: 'În așteptare',
    reimbursable: 'De recuperat',
    cancelled: 'Anulată',
};

const expenseStatusLabelsEn: Record<ExpenseStatus, string> = {
    paid: 'Paid',
    pending: 'Pending',
    reimbursable: 'Recoverable',
    cancelled: 'Cancelled',
};

export const expenseResponsiblePartyLabels: Record<
    ExpenseResponsibleParty,
    string
> = {
    owner: 'Proprietar',
    tenant: 'Chiriaș',
};

const expenseResponsiblePartyLabelsEn: Record<ExpenseResponsibleParty, string> = {
    owner: 'Owner',
    tenant: 'Renter',
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

const expenseSettlementTypeLabelsEn: Record<ExpenseSettlementType, string> = {
    none: 'No settlement',
    deduct_from_rent: 'Deduct from rent',
    deduct_from_utilities: 'Deduct from utilities',
    reimburse: 'Reimburse separately',
};

const settlementStateLabels: Record<ExpenseSettlementKind, string | null> = {
    none: null,
    reimbursement_due: 'De rambursat',
    reimbursed: 'Rambursat',
    recovery_due: 'De recuperat',
    recovered: 'Recuperat',
};

const settlementStateLabelsEn: Record<ExpenseSettlementKind, string | null> = {
    none: null,
    reimbursement_due: 'Reimbursement due',
    reimbursed: 'Reimbursed',
    recovery_due: 'Recovery due',
    recovered: 'Recovered',
};

const settlementActionLabels: Record<ExpenseSettlementKind, string | null> = {
    none: null,
    reimbursement_due: 'Marchează ca rambursat',
    reimbursed: 'Anulează rambursarea',
    recovery_due: 'Marchează ca recuperat',
    recovered: 'Anulează recuperarea',
};

const settlementActionLabelsEn: Record<ExpenseSettlementKind, string | null> = {
    none: null,
    reimbursement_due: 'Mark as reimbursed',
    reimbursed: 'Undo reimbursement',
    recovery_due: 'Mark as recovered',
    recovered: 'Undo recovery',
};

export function expenseCategoryLabel(value: ExpenseCategory) {
    const labels =
        currentAppLocale() === 'en'
            ? expenseCategoryLabelsEn
            : expenseCategoryLabels;

    return labels[value] ?? value;
}

export function expensePaidByLabel(value: ExpensePaidBy) {
    const labels =
        currentAppLocale() === 'en'
            ? expensePaidByLabelsEn
            : expensePaidByLabels;

    return labels[value] ?? value;
}

export function expenseResponsiblePartyLabel(value: ExpenseResponsibleParty) {
    const labels =
        currentAppLocale() === 'en'
            ? expenseResponsiblePartyLabelsEn
            : expenseResponsiblePartyLabels;

    return labels[value] ?? value;
}

export function expenseSettlementTypeLabel(
    value: ExpenseSettlementType,
    paidBy?: ExpensePaidBy,
    responsibleParty?: ExpenseResponsibleParty,
) {
    const isEnglish = currentAppLocale() === 'en';

    if (value === 'reimburse') {
        if (paidBy === 'owner' && responsibleParty === 'tenant') {
            return isEnglish
                ? 'Recover from renter'
                : 'Se recuperează de la chiriaș';
        }

        if (paidBy === 'tenant' && responsibleParty === 'owner') {
            return isEnglish
                ? 'Reimburse renter'
                : 'Se rambursează către chiriaș';
        }
    }

    const labels = isEnglish
        ? expenseSettlementTypeLabelsEn
        : expenseSettlementTypeLabels;

    return labels[value] ?? value;
}

export function expenseStatusLabel(value: ExpenseStatus) {
    const labels =
        currentAppLocale() === 'en'
            ? expenseStatusLabelsEn
            : expenseStatusLabels;

    return labels[value] ?? value;
}

export function expenseSettlementStateLabel(kind: ExpenseSettlementKind) {
    const labels =
        currentAppLocale() === 'en'
            ? settlementStateLabelsEn
            : settlementStateLabels;

    return labels[kind];
}

export function expenseSettlementActionLabel(kind: ExpenseSettlementKind) {
    const labels =
        currentAppLocale() === 'en'
            ? settlementActionLabelsEn
            : settlementActionLabels;

    return labels[kind];
}

export function expenseSettlementSettledLabel(
    kind: ExpenseSettlementKind,
    settledAt?: string | null,
) {
    if (!settledAt || (kind !== 'reimbursed' && kind !== 'recovered')) {
        return null;
    }

    const isEnglish = currentAppLocale() === 'en';
    const date = formatDateLong(settledAt);

    if (kind === 'reimbursed') {
        return isEnglish ? `Reimbursed on ${date}` : `Rambursat la ${date}`;
    }

    return isEnglish ? `Recovered on ${date}` : `Recuperat la ${date}`;
}
