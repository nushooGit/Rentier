<?php

test('shared frontend display date helper uses the long Romanian formatter', function () {
    $dateHelper = file_get_contents(resource_path('js/lib/date.ts'));

    expect($dateHelper)
        ->toContain("month: 'long'")
        ->and($dateHelper)->toContain('export const formatDateForDisplay = formatDateLong;')
        ->and($dateHelper)->toContain('export const formatDate = formatDateLong;')
        ->and($dateHelper)->toContain('export function formatDateForInput');
});

test('shared date input uses a Romanian text value and submits an ISO value', function () {
    $dateInput = file_get_contents(resource_path('js/components/date-input.tsx'));
    $dateHelper = file_get_contents(resource_path('js/lib/date.ts'));

    expect($dateInput)
        ->toContain('type="text"')
        ->and($dateInput)->toContain('type="hidden"')
        ->and($dateInput)->toContain('name={name}')
        ->and($dateInput)->toContain('value={isoValue}')
        ->and($dateInput)->toContain('parseDateInputToIso')
        ->and($dateInput)->toContain('Alege data din calendar')
        ->and($dateInput)->toContain('aria-required={required || undefined}')
        ->and($dateInput)->not->toContain('required={required}')
        ->and($dateInput)->toContain('const visibleServerError = hasFormatError ? undefined : error;')
        ->and($dateInput)->toContain('message={visibleServerError}')
        ->and($dateHelper)->toContain("return locale === 'ro-RO' ? 'ZZ.LL.AAAA' : 'YYYY-MM-DD';")
        ->and($dateHelper)->toContain('ROMANIAN_DATE_PATTERN');
});
