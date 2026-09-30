<?php

test('admin pages bypass the landlord application shell', function () {
    $app = file_get_contents(resource_path('js/app.tsx'));
    $adminLayout = file_get_contents(resource_path('js/layouts/admin-layout.tsx'));

    expect($app)
        ->toContain("case name.startsWith('admin/'):")
        ->toContain("case name === 'welcome':")
        ->not->toContain("case name.startsWith('admin/'):\n                return AppLayout");

    expect($adminLayout)
        ->toContain('Rentier Admin')
        ->toContain('Administrare internă')
        ->not->toContain("from '@/layouts/app-layout'");
});
