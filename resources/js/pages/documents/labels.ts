import { translateKey } from '@/lib/i18n';
import type { DocumentCategory } from '@/types';

const categoryKeys = {
    lease_contract: 'documents.category.leaseContract',
    addendum: 'documents.category.addendum',
    commodatum: 'documents.category.commodatum',
    handover_report: 'documents.category.handoverReport',
    invoice_receipt: 'documents.category.invoiceReceipt',
    property_document: 'documents.category.propertyDocument',
    other: 'documents.category.other',
} as const satisfies Record<DocumentCategory, Parameters<typeof translateKey>[0]>;

export function documentCategoryLabel(category: DocumentCategory) {
    return translateKey(categoryKeys[category]);
}
