import { currentAppLocale } from '@/lib/locale';
import type { DocumentCategory } from '@/types';

const labelsRo: Record<DocumentCategory, string> = {
    lease_contract: 'Contract de închiriere',
    addendum: 'Act adițional',
    commodatum: 'Comodat',
    handover_report: 'Proces-verbal',
    invoice_receipt: 'Factură / chitanță',
    property_document: 'Document proprietate',
    other: 'Altul',
};

const labelsEn: Record<DocumentCategory, string> = {
    lease_contract: 'Lease contract',
    addendum: 'Addendum',
    commodatum: 'Commodatum agreement',
    handover_report: 'Handover report',
    invoice_receipt: 'Invoice / receipt',
    property_document: 'Property document',
    other: 'Other',
};

export function documentCategoryLabel(value: DocumentCategory) {
    const labels = currentAppLocale() === 'en' ? labelsEn : labelsRo;

    return labels[value] ?? value;
}
