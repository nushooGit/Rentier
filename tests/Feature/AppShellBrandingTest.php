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
        ->toContain("t('nav.payments')")
        ->toContain("t('nav.expenses')")
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
        ->toContain("t('nav.properties')")
        ->toContain("t('nav.leases')")
        ->toContain("t('nav.payments')")
        ->toContain("t('nav.expenses')")
        ->toContain("t('nav.documents')");

    expect($translations)
        ->toContain("'app.tagline': 'Administrare chirii'")
        ->toContain("'nav.payments': 'Încasări'")
        ->toContain("'nav.expenses': 'Costuri'")
        ->toContain("'nav.payments': 'Income'")
        ->toContain("'nav.expenses': 'Costs'");
});

test('dashboard prioritizes primary landlord signals without changing financial source fields', function () {
    $dashboard = file_get_contents(resource_path('js/pages/dashboard.tsx'));

    expect($dashboard)
        ->toContain('Panou de control')
        ->toContain('Încasat luna asta')
        ->toContain('Rest de încasat')
        ->toContain('Costuri luna asta')
        ->toContain('De recuperat')
        ->toContain('Necesită atenția ta')
        ->toContain('Detalii financiare')
        ->toContain('summary.current_month_payments')
        ->toContain('summary.remaining_rent')
        ->toContain('summary.current_month_expenses')
        ->toContain('summary.recoverable_expenses');
});
