<?php

function pageSource(string $path): string
{
    return file_get_contents(resource_path("js/pages/{$path}"));
}

test('properties index includes card links and keeps action controls', function () {
    $source = pageSource('properties/index.tsx');

    expect($source)
        ->toContain('data-test="property-card-link"')
        ->toContain('href={show([currentTeamSlug, property.id])}')
        ->toContain('data-test="property-view-link"')
        ->toContain('data-test="property-edit-link"')
        ->toContain('data-test="property-delete-button"');
});

test('leases index includes card links and keeps action controls', function () {
    $source = pageSource('leases/index.tsx');

    expect($source)
        ->toContain('data-test="lease-card-link"')
        ->toContain('href={show([currentTeamSlug, lease.id])}')
        ->toContain('data-test="lease-view-link"')
        ->toContain('data-test="lease-edit-link"')
        ->toContain('data-test="lease-delete-button"');
});

test('payments index includes card links and keeps action controls', function () {
    $source = pageSource('payments/index.tsx');

    expect($source)
        ->toContain('data-test="payment-card-link"')
        ->toContain('href={show([currentTeamSlug, payment.id])}')
        ->toContain('Tip:')
        ->toContain('data-test="payment-view-link"')
        ->toContain('data-test="payment-edit-link"')
        ->toContain('deletePayment(payment)')
        ->toContain('data-test="payment-delete-button"');
});

test('expenses index includes card links and keeps action controls', function () {
    $source = pageSource('expenses/index.tsx');

    expect($source)
        ->toContain('data-test="expense-card-link"')
        ->toContain('href={show([currentTeamSlug, expense.id])}')
        ->toContain('settleExpense(expense)')
        ->toContain('data-test="expense-settlement-button"')
        ->toContain('data-test="expense-view-link"')
        ->toContain('data-test="expense-edit-link"')
        ->toContain('data-test="expense-delete-button"');
});

test('dashboard record lists include navigation links to record details', function () {
    $source = pageSource('dashboard.tsx');

    expect($source)
        ->toContain('data-test="dashboard-lease-link"')
        ->toContain('href={showLease([')
        ->toContain('data-test="dashboard-property-link"')
        ->toContain('href={showProperty([')
        ->toContain('data-test="dashboard-recent-lease-link"')
        ->toContain('data-test="dashboard-recent-payment-link"')
        ->toContain('data-test="dashboard-recent-expense-link"')
        ->toContain('href={showPayment([')
        ->toContain('href={showExpense([');
});

test('expense settlement labels use directional reimbursement wording', function () {
    $labels = pageSource('expenses/labels.ts');
    $index = pageSource('expenses/index.tsx');
    $form = pageSource('expenses/form.tsx');
    $show = pageSource('expenses/show.tsx');

    expect($labels)
        ->toContain('Se recuperează de la chiriaș')
        ->toContain('Se rambursează către chiriaș')
        ->toContain('Se scade din chirie')
        ->toContain('Se scade din utilități')
        ->toContain('Nu se decontează');

    expect($index)
        ->toContain('expense.paid_by')
        ->toContain('expense.responsible_party');

    expect($form)
        ->toContain('paidBy')
        ->toContain('responsibleParty')
        ->toContain('se recuperează de la chiriaș')
        ->toContain('se rambursează către chiriaș');

    expect($show)
        ->toContain('expense.paid_by')
        ->toContain('expense.responsible_party');
});
