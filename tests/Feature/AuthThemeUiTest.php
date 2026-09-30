<?php

test('authentication UI uses Rentier branding and the localization layer', function () {
    $layout = file_get_contents(resource_path('js/layouts/auth/auth-simple-layout.tsx'));
    $login = file_get_contents(resource_path('js/pages/auth/login.tsx'));
    $translations = file_get_contents(resource_path('js/lib/i18n.ts'));
    $baseView = file_get_contents(resource_path('views/app.blade.php'));

    expect($layout)
        ->toContain('Rentier')
        ->toContain("t('app.tagline')")
        ->toContain('<LocaleSwitcher')
        ->toContain('Proprietățile tale, organizate într-un singur loc.')
        ->not->toContain('AppLogoIcon');

    expect($translations)
        ->toContain("'app.tagline': 'Administrare chirii'")
        ->toContain("'app.tagline': 'Rental management'");

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

test('theme and locale controls are available on auth and authenticated app shells', function () {
    $toggle = file_get_contents(resource_path('js/components/theme-toggle.tsx'));
    $localeSwitcher = file_get_contents(resource_path('js/components/locale-switcher.tsx'));
    $translations = file_get_contents(resource_path('js/lib/i18n.ts'));
    $authLayout = file_get_contents(resource_path('js/layouts/auth/auth-simple-layout.tsx'));
    $appHeader = file_get_contents(resource_path('js/components/app-sidebar-header.tsx'));
    $appearance = file_get_contents(resource_path('js/components/appearance-tabs.tsx'));

    expect($toggle)
        ->toContain('useAppearance')
        ->toContain("'light'")
        ->toContain("'dark'")
        ->toContain("t('theme.enableLight')")
        ->toContain("t('theme.enableDark')");

    expect($translations)
        ->toContain("'theme.enableDark': 'Activează modul întunecat'")
        ->toContain("'theme.enableLight': 'Activează modul luminos'");

    expect($localeSwitcher)
        ->toContain('persistAppLocale')
        ->toContain("changeLocale('ro')")
        ->toContain("changeLocale('en')");

    expect($authLayout)
        ->toContain('<ThemeToggle')
        ->toContain('<LocaleSwitcher');

    expect($appHeader)
        ->toContain('<ThemeToggle')
        ->toContain('<LocaleSwitcher');

    expect($appearance)
        ->toContain('Luminos')
        ->toContain('Întunecat')
        ->toContain('Sistem');
});
