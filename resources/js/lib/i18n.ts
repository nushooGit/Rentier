import { usePage } from '@inertiajs/react';
import { normalizeAppLocale, type AppLocale } from '@/lib/locale';

const ro = {
    'app.tagline': 'Administrare chirii',
    'nav.main': 'Principal',
    'nav.dashboard': 'Dashboard',
    'nav.home': 'Acasă',
    'nav.properties': 'Proprietăți',
    'nav.leases': 'Contracte',
    'nav.payments': 'Încasări',
    'nav.expenses': 'Costuri',
    'nav.documents': 'Documente',
    'header.privateBeta': 'Beta privată',
    'header.workspaceFallback': 'Workspace Rentier',
    'team.select': 'Alege workspace',
    'team.workspaces': 'Workspace-uri',
    'team.new': 'Workspace nou',
    'user.settings': 'Setări',
    'user.logout': 'Deconectare',
    'theme.enableLight': 'Activează modul luminos',
    'theme.enableDark': 'Activează modul întunecat',
    'language.label': 'Limbă',
    'language.romanian': 'Română',
    'language.english': 'English',
    'mobile.primaryNavigation': 'Navigație principală',
} as const;

type TranslationKey = keyof typeof ro;

const en: Record<TranslationKey, string> = {
    'app.tagline': 'Rental management',
    'nav.main': 'Main',
    'nav.dashboard': 'Dashboard',
    'nav.home': 'Home',
    'nav.properties': 'Properties',
    'nav.leases': 'Leases',
    'nav.payments': 'Income',
    'nav.expenses': 'Costs',
    'nav.documents': 'Documents',
    'header.privateBeta': 'Private beta',
    'header.workspaceFallback': 'Rentier workspace',
    'team.select': 'Choose workspace',
    'team.workspaces': 'Workspaces',
    'team.new': 'New workspace',
    'user.settings': 'Settings',
    'user.logout': 'Log out',
    'theme.enableLight': 'Switch to light mode',
    'theme.enableDark': 'Switch to dark mode',
    'language.label': 'Language',
    'language.romanian': 'Română',
    'language.english': 'English',
    'mobile.primaryNavigation': 'Primary navigation',
};

const messages: Record<AppLocale, Record<TranslationKey, string>> = { ro, en };

export function useI18n() {
    const page = usePage();
    const locale = normalizeAppLocale(
        typeof page.props.locale === 'string' ? page.props.locale : null,
    );

    return {
        locale,
        t: (key: TranslationKey) => messages[locale][key] ?? ro[key],
    };
}

export type { TranslationKey };
