export type AppLocale = 'ro' | 'en';

export const DEFAULT_APP_LOCALE: AppLocale = 'ro';
export const DEFAULT_LOCALE = 'ro-RO';

export const SUPPORTED_LOCALES: readonly AppLocale[] = ['ro', 'en'];

export function normalizeAppLocale(locale?: string | null): AppLocale {
    return locale === 'en' ? 'en' : DEFAULT_APP_LOCALE;
}

export function intlLocale(locale?: string | null) {
    return normalizeAppLocale(locale) === 'en' ? 'en-GB' : DEFAULT_LOCALE;
}

export function currentAppLocale(): AppLocale {
    if (typeof document === 'undefined') {
        return DEFAULT_APP_LOCALE;
    }

    const cookieLocale = document.cookie
        .split('; ')
        .find((cookie) => cookie.startsWith('rentier_locale='))
        ?.split('=')[1];

    return normalizeAppLocale(cookieLocale);
}

export function currentIntlLocale() {
    return intlLocale(currentAppLocale());
}

export function persistAppLocale(locale: AppLocale) {
    if (typeof document === 'undefined') {
        return;
    }

    const secure = window.location.protocol === 'https:' ? '; Secure' : '';

    document.cookie = `rentier_locale=${locale}; Path=/; Max-Age=31536000; SameSite=Lax${secure}`;
    window.localStorage.setItem('rentier_locale', locale);
}
