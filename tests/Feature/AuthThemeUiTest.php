<?php

test('authentication UI uses Romanian Rentier copy and branded layout', function () {
    $layout = file_get_contents(resource_path('js/layouts/auth/auth-simple-layout.tsx'));
    $login = file_get_contents(resource_path('js/pages/auth/login.tsx'));
    $baseView = file_get_contents(resource_path('views/app.blade.php'));

    expect($layout)
        ->toContain('Rentier')
        ->toContain('Administrare chirii')
        ->toContain('Proprietățile tale, organizate într-un singur loc.')
        ->not->toContain('AppLogoIcon');

    expect($login)
        ->toContain('Bine ai revenit')
        ->toContain('Adresă de email')
        ->toContain('Parolă')
        ->toContain('Ține-mă minte')
        ->toContain('Autentificare')
        ->toContain('Intră cu passkey')
        ->not->toContain('Log in to your account');

    expect($baseView)
        ->toContain("config('app.name', 'Rentier')")
        ->not->toContain("config('app.name', 'Laravel')");
});

test('theme toggle is available on auth and authenticated app shells', function () {
    $toggle = file_get_contents(resource_path('js/components/theme-toggle.tsx'));
    $authLayout = file_get_contents(resource_path('js/layouts/auth/auth-simple-layout.tsx'));
    $appHeader = file_get_contents(resource_path('js/components/app-sidebar-header.tsx'));
    $appearance = file_get_contents(resource_path('js/components/appearance-tabs.tsx'));

    expect($toggle)
        ->toContain('useAppearance')
        ->toContain("'light'")
        ->toContain("'dark'")
        ->toContain('Activează modul întunecat')
        ->toContain('Activează modul luminos');

    expect($authLayout)
        ->toContain('<ThemeToggle');

    expect($appHeader)
        ->toContain('<ThemeToggle');

    expect($appearance)
        ->toContain('Luminos')
        ->toContain('Întunecat')
        ->toContain('Sistem');
});
