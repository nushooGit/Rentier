<?php

test('authenticated app shell uses Rentier branding instead of starter kit links', function () {
    $logo = file_get_contents(resource_path('js/components/app-logo.tsx'));
    $sidebar = file_get_contents(resource_path('js/components/app-sidebar.tsx'));
    $navigation = file_get_contents(resource_path('js/components/nav-main.tsx'));
    $workspace = file_get_contents(resource_path('js/components/team-switcher.tsx'));
    $mobileNavigation = file_get_contents(resource_path('js/components/mobile-bottom-nav.tsx'));

    expect($logo)
        ->toContain('Rentier')
        ->toContain('Administrare chirii')
        ->not->toContain('Laravel Starter Kit');

    expect($sidebar)
        ->not->toContain('laravel/react-starter-kit')
        ->not->toContain('laravel.com/docs');

    expect($navigation)
        ->toContain('Principal');

    expect($workspace)
        ->toContain('Workspace-uri')
        ->toContain('Workspace nou')
        ->toContain('Alege workspace');

    expect($mobileNavigation)
        ->toContain("label: 'Acasă'")
        ->toContain("label: 'Proprietăți'")
        ->toContain("label: 'Contracte'")
        ->toContain("label: 'Plăți'")
        ->toContain("label: 'Cheltuieli'")
        ->toContain("label: 'Documente'");
});

test('dashboard prioritizes primary landlord signals without changing financial source fields', function () {
    $dashboard = file_get_contents(resource_path('js/pages/dashboard.tsx'));

    expect($dashboard)
        ->toContain('Panou de control')
        ->toContain('Încasat luna asta')
        ->toContain('Rest de încasat')
        ->toContain('Cheltuieli luna asta')
        ->toContain('De recuperat')
        ->toContain('Necesită atenția ta')
        ->toContain('Detalii financiare')
        ->toContain('summary.current_month_payments')
        ->toContain('summary.remaining_rent')
        ->toContain('summary.current_month_expenses')
        ->toContain('summary.recoverable_expenses');
});
