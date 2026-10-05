<?php

use App\Enums\UtilityAccountStatus;
use App\Enums\UtilityBillStatus;
use App\Enums\UtilityResponsibleParty;
use App\Enums\UtilityServiceType;
use App\Models\Expense;
use App\Models\Lease;
use App\Models\Property;
use App\Models\RentPayment;
use App\Models\Team;
use App\Models\User;
use App\Models\UtilityAccount;
use App\Models\UtilityBill;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

test('exports index is available to a workspace member', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $this
        ->actingAs($user)
        ->get(route('exports.index', $team))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('exports/index'));
});

test('property csv export is scoped to the current workspace', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    Property::factory()->for($team)->create(['name' => 'Apartament Exportat']);
    Property::factory()->create(['name' => 'Apartament Străin']);

    $response = $this
        ->actingAs($user)
        ->get(route('exports.download', [$team, 'properties']))
        ->assertOk();

    $content = $response->streamedContent();

    expect($content)
        ->toContain('Apartament Exportat')
        ->not->toContain('Apartament Străin');
});

test('lease payment and expense exports include current workspace records', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $property = Property::factory()->for($team)->create(['name' => 'Central 10']);
    $lease = Lease::factory()->for($team)->create([
        'property_id' => $property->id,
        'monthly_rent_amount' => '2500.00',
    ]);
    $payment = RentPayment::factory()->for($team)->create([
        'lease_id' => $lease->id,
        'property_id' => $property->id,
        'renter_id' => $lease->renter_id,
        'amount' => '1250.00',
        'notes' => 'Plată export',
    ]);
    Expense::factory()->for($team)->create([
        'property_id' => $property->id,
        'lease_id' => $lease->id,
        'title' => 'Reparație export',
        'amount' => '300.00',
    ]);

    $leaseCsv = $this
        ->actingAs($user)
        ->get(route('exports.download', [$team, 'leases']))
        ->assertOk()
        ->streamedContent();

    $paymentCsv = $this
        ->actingAs($user)
        ->get(route('exports.download', [$team, 'payments']))
        ->assertOk()
        ->streamedContent();

    $expenseCsv = $this
        ->actingAs($user)
        ->get(route('exports.download', [$team, 'expenses']))
        ->assertOk()
        ->streamedContent();

    expect($leaseCsv)->toContain('Central 10');
    expect($paymentCsv)
        ->toContain((string) $payment->amount)
        ->toContain('Plată export');
    expect($expenseCsv)->toContain('Reparație export');
});

test('utility export keeps invoice amount previous balance and total due separate', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $property = Property::factory()->for($team)->create(['name' => 'PPC Export']);

    $account = UtilityAccount::create([
        'team_id' => $team->id,
        'property_id' => $property->id,
        'lease_id' => null,
        'provider_name' => 'PPC Energie',
        'service_type' => UtilityServiceType::Electricity,
        'account_identifier' => 'CLIENT-123',
        'responsible_party' => UtilityResponsibleParty::Owner,
        'status' => UtilityAccountStatus::Active,
        'notes' => null,
    ]);

    UtilityBill::create([
        'team_id' => $team->id,
        'utility_account_id' => $account->id,
        'property_id' => $property->id,
        'lease_id' => null,
        'document_id' => null,
        'created_by_user_id' => $user->id,
        'invoice_number' => 'PPC-2026-10',
        'provider_invoice_id' => 'INV-PPC',
        'payment_code' => 'PAY-123',
        'billing_period_start' => '2026-09-01',
        'billing_period_end' => '2026-09-30',
        'issue_date' => '2026-10-01',
        'due_date' => '2026-10-15',
        'amount_minor' => 26382,
        'previous_balance_minor' => 43938,
        'total_due_minor' => 70320,
        'currency' => 'RON',
        'status' => UtilityBillStatus::Unpaid,
        'responsible_party' => UtilityResponsibleParty::Owner,
        'paid_by' => null,
        'paid_on' => null,
        'notes' => null,
    ]);

    $content = $this
        ->actingAs($user)
        ->get(route('exports.download', [$team, 'utilities']))
        ->assertOk()
        ->streamedContent();

    expect($content)
        ->toContain('PPC Energie')
        ->toContain('263.82')
        ->toContain('439.38')
        ->toContain('703.20');
});

test('csv export is utf 8 and neutralizes spreadsheet formulas from user text', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    Property::factory()->for($team)->create([
        'name' => '=1+1',
        'notes' => '@SUM(A1:A2)',
    ]);

    $content = $this
        ->actingAs($user)
        ->get(route('exports.download', [$team, 'properties']))
        ->assertOk()
        ->streamedContent();

    expect(str_starts_with($content, "\xEF\xBB\xBF"))->toBeTrue();
    expect($content)
        ->toContain("'=1+1")
        ->toContain("'@SUM(A1:A2)");
});

test('property export includes the active renter and localizes property values', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $property = Property::factory()->for($team)->create([
        'name' => 'Apartament Localizat',
        'type' => 'apartment',
    ]);
    $lease = Lease::factory()->for($team)->create([
        'property_id' => $property->id,
        'start_date' => today()->subMonth()->toDateString(),
        'end_date' => null,
    ]);
    $lease->renter->update(['name' => 'Ana Chiriaș']);

    $content = $this
        ->actingAs($user)
        ->get(route('exports.download', [$team, 'properties']))
        ->assertOk()
        ->streamedContent();

    expect($content)
        ->toContain('Tip proprietate')
        ->toContain('Chiriaș')
        ->toContain('Apartament')
        ->toContain('România')
        ->toContain('Ocupat')
        ->toContain('Ana Chiriaș')
        ->not->toContain(';apartment;')
        ->not->toContain(';occupied;');
});

test('all csv datasets localize stored domain values in Romanian', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $property = Property::factory()->for($team)->create(['name' => 'Localizare CSV']);
    $lease = Lease::factory()->for($team)->create([
        'property_id' => $property->id,
        'start_date' => today()->subMonth()->toDateString(),
        'end_date' => null,
    ]);

    RentPayment::factory()->for($team)->create([
        'lease_id' => $lease->id,
        'property_id' => $property->id,
        'renter_id' => $lease->renter_id,
        'payment_type' => 'guarantee',
        'method' => 'bank_transfer',
        'status' => 'partial',
    ]);

    Expense::factory()->for($team)->create([
        'property_id' => $property->id,
        'lease_id' => $lease->id,
        'category' => 'maintenance',
        'paid_by' => 'tenant',
        'responsible_party' => 'owner',
        'settlement_type' => 'deduct_from_rent',
        'status' => 'reimbursable',
    ]);

    $account = UtilityAccount::create([
        'team_id' => $team->id,
        'property_id' => $property->id,
        'lease_id' => $lease->id,
        'provider_name' => 'Furnizor Localizare',
        'service_type' => UtilityServiceType::Electricity,
        'account_identifier' => 'LOC-001',
        'responsible_party' => UtilityResponsibleParty::Renter,
        'status' => UtilityAccountStatus::Active,
        'notes' => null,
    ]);

    UtilityBill::create([
        'team_id' => $team->id,
        'utility_account_id' => $account->id,
        'property_id' => $property->id,
        'lease_id' => $lease->id,
        'document_id' => null,
        'created_by_user_id' => $user->id,
        'invoice_number' => 'LOC-2026',
        'provider_invoice_id' => null,
        'payment_code' => null,
        'billing_period_start' => '2026-09-01',
        'billing_period_end' => '2026-09-30',
        'issue_date' => '2026-10-01',
        'due_date' => '2026-10-15',
        'amount_minor' => 10000,
        'previous_balance_minor' => null,
        'total_due_minor' => 10000,
        'currency' => 'RON',
        'status' => UtilityBillStatus::Unpaid,
        'responsible_party' => UtilityResponsibleParty::Renter,
        'paid_by' => UtilityResponsibleParty::Owner,
        'paid_on' => null,
        'notes' => null,
    ]);

    $leaseCsv = $this->actingAs($user)
        ->get(route('exports.download', [$team, 'leases']))
        ->assertOk()
        ->streamedContent();
    $paymentCsv = $this->actingAs($user)
        ->get(route('exports.download', [$team, 'payments']))
        ->assertOk()
        ->streamedContent();
    $expenseCsv = $this->actingAs($user)
        ->get(route('exports.download', [$team, 'expenses']))
        ->assertOk()
        ->streamedContent();
    $utilityCsv = $this->actingAs($user)
        ->get(route('exports.download', [$team, 'utilities']))
        ->assertOk()
        ->streamedContent();

    expect($leaseCsv)
        ->toContain('Activ')
        ->not->toContain(';active;');

    expect($paymentCsv)
        ->toContain('Garanție')
        ->toContain('Transfer bancar')
        ->toContain('Parțial achitată')
        ->not->toContain(';guarantee;')
        ->not->toContain(';bank_transfer;');

    expect($expenseCsv)
        ->toContain('Întreținere')
        ->toContain('Chiriaș')
        ->toContain('Proprietar')
        ->toContain('Scădere din chirie')
        ->toContain('De decontat')
        ->not->toContain(';maintenance;')
        ->not->toContain(';deduct_from_rent;');

    expect($utilityCsv)
        ->toContain('Electricitate')
        ->toContain('Neachitată')
        ->toContain('Chiriaș')
        ->toContain('Proprietar')
        ->not->toContain(';electricity;')
        ->not->toContain(';unpaid;');
});

test('csv value labels follow the active English locale', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $property = Property::factory()->for($team)->create([
        'type' => 'commercial_space',
    ]);
    $lease = Lease::factory()->for($team)->create([
        'property_id' => $property->id,
        'start_date' => today()->subMonth()->toDateString(),
        'end_date' => null,
    ]);
    $lease->renter->update(['name' => 'English Renter']);

    $content = $this
        ->actingAs($user)
        ->withCookie('rentier_locale', 'en')
        ->get(route('exports.download', [$team, 'properties']))
        ->assertOk()
        ->streamedContent();

    expect($content)
        ->toContain('Property type')
        ->toContain('Renter')
        ->toContain('Commercial space')
        ->toContain('Occupied')
        ->toContain('English Renter')
        ->not->toContain(';commercial_space;')
        ->not->toContain(';occupied;');
});

test('unknown export dataset returns not found', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $this
        ->actingAs($user)
        ->get(route('exports.download', [$team, 'unknown']))
        ->assertNotFound();
});

test('a user cannot export another workspace through the workspace route', function () {
    $user = User::factory()->create();
    $otherTeam = Team::factory()->create();

    $this
        ->actingAs($user)
        ->get(route('exports.download', [$otherTeam, 'properties']))
        ->assertForbidden();
});
