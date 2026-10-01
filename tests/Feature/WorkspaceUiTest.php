<?php

test('workspace management uses Rentier terminology and localization', function () {
    $paths = [
        'js/pages/teams/index.tsx',
        'js/pages/teams/edit.tsx',
        'js/components/create-team-modal.tsx',
        'js/components/invite-member-modal.tsx',
        'js/components/pending-invitations-modal.tsx',
        'js/components/cancel-invitation-modal.tsx',
        'js/components/delete-team-modal.tsx',
        'js/components/leave-team-modal.tsx',
        'js/components/remove-member-modal.tsx',
    ];

    foreach ($paths as $path) {
        $source = file_get_contents(resource_path($path));

        expect($source)->toContain('useI18n');
    }

    $index = file_get_contents(resource_path('js/pages/teams/index.tsx'));
    $edit = file_get_contents(resource_path('js/pages/teams/edit.tsx'));
    $translations = file_get_contents(resource_path('js/lib/i18n.ts'));
    $appearance = file_get_contents(resource_path('js/pages/settings/appearance.tsx'));
    $profile = file_get_contents(resource_path('js/pages/settings/profile.tsx'));

    expect($index)
        ->toContain("t('workspaces.index.title')")
        ->toContain('workspaceRoleLabel')
        ->not->toContain('title="Teams"')
        ->not->toContain('Leave team')
        ->not->toContain('View team')
        ->not->toContain('Edit team');

    expect($edit)
        ->toContain("t('workspaces.edit.settingsTitle')")
        ->toContain("t('workspaces.edit.members')")
        ->toContain('workspaceRoleLabel')
        ->not->toContain('Team settings')
        ->not->toContain('Team members')
        ->not->toContain('Delete team');

    expect($translations)
        ->toContain("'workspaces.index.title': 'Workspace-uri'")
        ->toContain("'workspaces.index.title': 'Workspaces'")
        ->toContain("'workspaces.role.owner': 'Proprietar'")
        ->toContain("'workspaces.role.owner': 'Owner'");

    expect($appearance)
        ->toContain("translateKey('settings.appearance.title')");

    expect($profile)
        ->toContain("translateKey('settings.profile.title')");
});

test('workspace operation messages avoid team terminology in user-facing copy', function () {
    $controller = file_get_contents(app_path('Http/Controllers/Teams/TeamController.php'));
    $memberController = file_get_contents(app_path('Http/Controllers/Teams/TeamMemberController.php'));
    $translations = file_get_contents(lang_path('ro.json'));

    expect($controller)
        ->toContain("__('Workspace created.')")
        ->toContain("__('Workspace updated.')")
        ->toContain("__('Workspace deleted.')")
        ->not->toContain("__('Team created.')")
        ->not->toContain("__('Team updated.')")
        ->not->toContain("__('Team deleted.')");

    expect($memberController)
        ->toContain("__('The workspace owner cannot be removed.')")
        ->not->toContain("__('The team owner cannot be removed.')");

    expect($translations)
        ->toContain('"Workspace created.": "Workspace-ul a fost creat."')
        ->toContain('"Member removed.": "Membrul a fost eliminat."');
});
