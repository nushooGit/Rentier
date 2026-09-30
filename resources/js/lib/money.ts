import { currentIntlLocale } from '@/lib/locale';

export function formatMoney(amount?: string | null, currency = 'RON') {
    if (!amount) {
        return `0 ${currency}`;
    }

    return `${Number(amount).toLocaleString(currentIntlLocale(), {
        maximumFractionDigits: 2,
        minimumFractionDigits: 0,
    })} ${currency}`;
}
