<?php

test('authenticated app shell uses Rentier branding and localized product navigation', function () {
    $logo = file_get_contents(resource_path('js/components/app-logo.tsx'));
    $sidebar = file_get_contents(resource_path('js/components/app-sidebar.tsx'));
    $navigation = file_get_contents(resource_path('js/components/nav-main.tsx'));
    $workspace = file_get_contents(resource_path('js/components/team-switcher.tsx'));
    $mobileNavigation = file_get_contents(resource_path('js/components/mobile-bottom-nav.tsx'));
    $translations = file_get_contents(resource_path('js/lib/i18n.ts'));

    expect($logo)
        ->toContain('Rentier')
        ->toContain("t('app.tagline')")
        ->not->toContain('Laravel Starter Kit');

    expect($sidebar)
        ->toContain("t('nav.calendar')")
        ->toContain("t('nav.payments')")
        ->toContain("t('nav.expenses')")
        ->toContain("t('nav.utilities')")
        ->toContain("t('nav.documents')")
        ->toContain("t('nav.exports')")
        ->not->toContain('laravel/react-starter-kit')
        ->not->toContain('laravel.com/docs');

    expect($navigation)
        ->toContain("t('nav.main')");

    expect($workspace)
        ->toContain("t('team.workspaces')")
        ->toContain("t('team.new')")
        ->toContain("t('team.select')");

    expect($mobileNavigation)
        ->toContain("t('nav.home')")
        ->toContain("t('nav.calendar')")
        ->toContain("t('nav.properties')")
        ->toContain("t('nav.leases')")
        ->toContain("t('nav.payments')")
        ->toContain("t('nav.expenses')")
        ->not->toContain("t('nav.utilities')")
        ->not->toContain("t('nav.documents')");

    expect($translations)
        ->toContain("'app.tagline': 'Administrare chirii'")
        ->toContain("'nav.calendar': 'Calendar'")
        ->toContain("'nav.payments': 'Încasări'")
        ->toContain("'nav.expenses': 'Costuri'")
        ->toContain("'nav.utilities': 'Utilități'")
        ->toContain("'nav.exports': 'Exporturi'")
        ->toContain("'nav.payments': 'Income'")
        ->toContain("'nav.expenses': 'Costs'")
        ->toContain("'nav.utilities': 'Utilities'")
        ->toContain("'nav.exports': 'Exports'");
});

test('dashboard prioritizes primary landlord signals without changing financial source fields', function () {
    $dashboard = file_get_contents(resource_path('js/pages/dashboard.tsx'));
    $translations = file_get_contents(resource_path('js/lib/i18n.ts'));

    expect($dashboard)
        ->toContain("t('dashboard.title')")
        ->toContain("t('dashboard.collectedThisMonth')")
        ->toContain("t('dashboard.remaining')")
        ->toContain("t('dashboard.costsThisMonth')")
        ->toContain("t('dashboard.recoverable')")
        ->toContain("t('dashboard.attention')")
        ->toContain("t('dashboard.financialDetails')")
        ->toContain('summary.current_month_payments')
        ->toContain('summary.remaining_rent')
        ->toContain('summary.current_month_expenses')
        ->toContain('summary.recoverable_expenses');

    expect($translations)
        ->toContain("'dashboard.title': 'Panou de control'")
        ->toContain("'dashboard.title': 'Dashboard'")
        ->toContain("'dashboard.collectedThisMonth': 'Încasat luna asta'")
        ->toContain("'dashboard.collectedThisMonth': 'Collected this month'");
});
