<?php

test('shared frontend display date helper uses the long Romanian formatter', function () {
    $dateHelper = file_get_contents(resource_path('js/lib/date.ts'));

    expect($dateHelper)
        ->toContain("month: 'long'")
        ->and($dateHelper)->toContain('export const formatDateForDisplay = formatDateLong;')
        ->and($dateHelper)->toContain('export const formatDate = formatDateLong;')
        ->and($dateHelper)->toContain('export function formatDateForInput');
});
