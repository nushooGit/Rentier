<?php

test('english landlord surfaces do not keep Romanian runtime copy in the affected UI files', function () {
    $paths = [
        'js/pages/expenses/index.tsx',
        'js/pages/expenses/show.tsx',
        'js/pages/expenses/form.tsx',
        'js/pages/payments/index.tsx',
        'js/pages/payments/show.tsx',
        'js/pages/payments/form.tsx',
        'js/pages/documents/index.tsx',
        'js/pages/leases/form.tsx',
        'js/pages/properties/show.tsx',
        'js/pages/properties/index.tsx',
    ];

    $forbidden = [
        'Plătit de',
        'Suportat de',
        'Decontare:',
        'Rambursat',
        'Recuperat',
        'Contract de închiriere',
        'Chirie parțial achitată',
        'Garanție achitată integral',
        'Data început',
        'Data sfârșit',
        'Telefon chiriaș',
        'Chirie lunară',
        'Ziua scadentă a chiriei',
        'Suprafață utilă',
        'Suprafață totală',
        'Nesetat',
        'Alege proprietatea',
        'Alege contractul',
        'Fără contract',
        'Plătită parțial',
        'Restanță:',
        'luni restante',
        'Scăzut din chirie',
        'Încasat:',
    ];

    foreach ($paths as $path) {
        $source = file_get_contents(resource_path($path));

        foreach ($forbidden as $text) {
            expect($source)
                ->not->toContain($text, "{$path} still contains Romanian runtime copy: {$text}");
        }
    }
});

test('runtime labels are derived from stable semantic values instead of backend Romanian labels', function () {
    $payments = file_get_contents(resource_path('js/pages/payments/labels.ts'));
    $paymentIndex = file_get_contents(resource_path('js/pages/payments/index.tsx'));
    $expenses = file_get_contents(resource_path('js/pages/expenses/labels.ts'));
    $expenseIndex = file_get_contents(resource_path('js/pages/expenses/index.tsx'));
    $documents = file_get_contents(resource_path('js/pages/documents/labels.ts'));
    $documentIndex = file_get_contents(resource_path('js/pages/documents/index.tsx'));
    $propertyLabels = file_get_contents(resource_path('js/pages/properties/labels.ts'));
    $propertyIndex = file_get_contents(resource_path('js/pages/properties/index.tsx'));

    expect($payments)
        ->toContain('paymentSummaryStatusLabel')
        ->toContain('paymentPeriodLabelFromDate');

    expect($paymentIndex)
        ->toContain('payment.status_summary.status_key')
        ->toContain('allocation.period_date')
        ->not->toContain('payment.status_summary.status_label')
        ->not->toContain('allocation.period_label');

    expect($expenses)
        ->toContain('expenseSettlementStateLabel')
        ->toContain('expenseSettlementActionLabel')
        ->toContain('expenseSettlementSettledLabel');

    expect($expenseIndex)
        ->toContain('expense.settlement_state.kind')
        ->not->toContain('expense.settlement_state.label')
        ->not->toContain('expense.settlement_state.action_label')
        ->not->toContain('expense.settlement_state.settled_label');

    expect($documents)
        ->toContain('documentCategoryLabel');

    expect($documentIndex)
        ->toContain('documentCategoryLabel(category.value)')
        ->toContain('documentCategoryLabel(document.category)')
        ->not->toContain('{category.label}')
        ->not->toContain('{document.category_label}');

    expect($propertyLabels)
        ->toContain('propertyRentStatusBadgeLabel')
        ->toContain('propertyAdvanceNoticeLabel');

    expect($propertyIndex)
        ->toContain('propertyRentStatusBadgeLabel')
        ->toContain('propertyAdvanceNoticeLabel')
        ->not->toContain('{badge.label}')
        ->not->toContain('{notice.label}');
});
