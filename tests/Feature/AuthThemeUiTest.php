<?php

test('authentication UI uses Rentier branding and the localization layer', function () {
    $layout = file_get_contents(resource_path('js/layouts/auth/auth-simple-layout.tsx'));
    $login = file_get_contents(resource_path('js/pages/auth/login.tsx'));
    $register = file_get_contents(resource_path('js/pages/auth/register.tsx'));
    $passwordInput = file_get_contents(resource_path('js/components/password-input.tsx'));
    $passwordRequirements = file_get_contents(resource_path('js/components/password-requirements.tsx'));
    $passkeyItem = file_get_contents(resource_path('js/components/passkey-item.tsx'));
    $passkeyRegister = file_get_contents(resource_path('js/components/passkey-register.tsx'));
    $twoFactorCodes = file_get_contents(resource_path('js/components/two-factor-recovery-codes.tsx'));
    $twoFactorSetup = file_get_contents(resource_path('js/components/two-factor-setup-modal.tsx'));
    $translations = file_get_contents(resource_path('js/lib/i18n.ts'));
    $baseView = file_get_contents(resource_path('views/app.blade.php'));

    expect($layout)
        ->toContain('Rentier')
        ->toContain("t('app.tagline')")
        ->toContain("t('auth.shell.heroTitle')")
        ->toContain('<LocaleSwitcher')
        ->not->toContain('AppLogoIcon');

    expect($translations)
        ->toContain("'app.tagline': 'Administrare chirii'")
        ->toContain("'app.tagline': 'Rental management'")
        ->toContain("'auth.login.title': 'Bine ai revenit'")
        ->toContain("'auth.login.title': 'Welcome back'")
        ->toContain("'auth.login.email': 'Adresă de email'")
        ->toContain("'auth.login.email': 'Email address'");

    expect($login)
        ->toContain("title: 'auth.login.title'")
        ->toContain("description: 'auth.login.description'")
        ->toContain("t('auth.login.email')")
        ->toContain("t('auth.login.password')")
        ->toContain("t('auth.login.remember')")
        ->toContain("t('auth.login.submit')")
        ->toContain("t('auth.login.passkey')")
        ->not->toContain('Log in to your account');

    expect($register)
        ->toContain("title: 'auth.register.title'")
        ->toContain("t('auth.register.name')")
        ->toContain("t('auth.register.email')")
        ->toContain("t('auth.register.password')")
        ->toContain('<PasswordRequirements')
        ->not->toContain('Create an account');

    expect($passwordInput)
        ->toContain("t('password.show')")
        ->toContain("t('password.hide')")
        ->not->toContain('Afișează parola')
        ->not->toContain('Ascunde parola');

    expect($passwordRequirements)
        ->toContain("t('password.requirements.title')")
        ->toContain("t('password.requirements.length')")
        ->toContain("t('password.requirements.symbol')");

    expect($passkeyItem)
        ->toContain("t('settings.passkeys.removeTitle')")
        ->not->toContain('Remove passkey');

    expect($passkeyRegister)
        ->toContain("t('settings.passkeys.add')")
        ->toContain("t('settings.passkeys.name')")
        ->not->toContain('Passkeys are not supported in this browser.');

    expect($twoFactorCodes)
        ->toContain("t('settings.twoFactor.recoveryTitle')")
        ->toContain("t('settings.twoFactor.regenerateCodes')")
        ->not->toContain('2FA recovery codes');

    expect($twoFactorSetup)
        ->toContain("t('settings.twoFactor.enableTitle')")
        ->toContain("t('settings.twoFactor.verifyTitle')")
        ->not->toContain('Two-factor authentication enabled');

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
        ->toContain("'theme.enableLight': 'Activează modul luminos'")
        ->toContain("'settings.appearance.light': 'Luminos'")
        ->toContain("'settings.appearance.light': 'Light'");

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
        ->toContain("t('settings.appearance.light')")
        ->toContain("t('settings.appearance.dark')")
        ->toContain("t('settings.appearance.system')");
});

test('settings UI uses the shared localization layer', function () {
    $layout = file_get_contents(resource_path('js/layouts/settings/layout.tsx'));
    $profile = file_get_contents(resource_path('js/pages/settings/profile.tsx'));
    $security = file_get_contents(resource_path('js/pages/settings/security.tsx'));
    $deleteUser = file_get_contents(resource_path('js/components/delete-user.tsx'));
    $translations = file_get_contents(resource_path('js/lib/i18n.ts'));

    expect($layout)
        ->toContain("t('settings.title')")
        ->toContain("t('settings.nav.profile')")
        ->toContain("t('settings.nav.security')");

    expect($profile)
        ->toContain("t('settings.profile.title')")
        ->toContain("t('settings.save')");

    expect($security)
        ->toContain("t('settings.security.passwordTitle')")
        ->toContain("t('settings.security.currentPassword')")
        ->toContain('<PasswordRequirements')
        ->toContain("translateKey('settings.security.title')");

    expect($deleteUser)
        ->toContain("t('settings.delete.title')")
        ->toContain("t('settings.delete.confirmTitle')");

    expect($translations)
        ->toContain("'settings.title': 'Setări'")
        ->toContain("'settings.title': 'Settings'");
});
