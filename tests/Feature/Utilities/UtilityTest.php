<?php

use App\Enums\DocumentCategory;
use App\Models\Document;
use App\Models\Lease;
use App\Models\Property;
use App\Models\Team;
use App\Models\User;
use App\Models\UtilityAccount;
use App\Models\UtilityBill;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

afterEach(function () {
    Carbon::setTestNow();
});

function createUtilityAccountFor(Team $team, Property $property, ?Lease $lease = null): UtilityAccount
{
    return UtilityAccount::query()->create([
        'team_id' => $team->id,
        'property_id' => $property->id,
        'lease_id' => $lease?->id,
        'provider_name' => 'Electrica Test',
        'service_type' => 'electricity',
        'account_identifier' => 'CLIENT-123',
        'responsible_party' => $lease ? 'renter' : 'owner',
        'status' => 'active',
    ]);
}

test('workspace member can create and update a utility account', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $property = Property::factory()->for($team)->create();

    $this
        ->actingAs($user)
        ->post(route('utility-accounts.store', $team), [
            'property_id' => $property->id,
            'lease_id' => null,
            'provider_name' => 'Apa Nova',
            'service_type' => 'water',
            'account_identifier' => 'AN-100',
            'responsible_party' => 'owner',
            'status' => 'active',
            'notes' => 'Cont principal',
        ])
        ->assertRedirect();

    $account = UtilityAccount::query()->firstOrFail();

    expect($account)
        ->team_id->toBe($team->id)
        ->property_id->toBe($property->id)
        ->provider_name->toBe('Apa Nova')
        ->account_identifier->toBe('AN-100');

    $this
        ->actingAs($user)
        ->put(route('utility-accounts.update', [$team, $account]), [
            'property_id' => $property->id,
            'lease_id' => null,
            'provider_name' => 'Apa Nova București',
            'service_type' => 'water',
            'account_identifier' => 'AN-100',
            'responsible_party' => 'owner',
            'status' => 'inactive',
            'notes' => null,
        ])
        ->assertRedirect();

    expect($account->fresh())
        ->provider_name->toBe('Apa Nova București')
        ->status->value->toBe('inactive');
});

test('renter responsible utility account requires a lease on the same property', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $property = Property::factory()->for($team)->create();
    $otherProperty = Property::factory()->for($team)->create();
    $otherLease = Lease::factory()->for($team)->create([
        'property_id' => $otherProperty->id,
    ]);

    $this
        ->actingAs($user)
        ->post(route('utility-accounts.store', $team), [
            'property_id' => $property->id,
            'lease_id' => null,
            'provider_name' => 'Furnizor',
            'service_type' => 'gas',
            'responsible_party' => 'renter',
            'status' => 'active',
        ])
        ->assertSessionHasErrors('lease_id');

    $this
        ->actingAs($user)
        ->post(route('utility-accounts.store', $team), [
            'property_id' => $property->id,
            'lease_id' => $otherLease->id,
            'provider_name' => 'Furnizor',
            'service_type' => 'gas',
            'responsible_party' => 'renter',
            'status' => 'active',
        ])
        ->assertSessionHasErrors('lease_id');
});

test('workspace member can create a utility bill with private invoice attachment', function () {
    Storage::fake('local');

    $user = User::factory()->create();
    $team = $user->currentTeam;
    $property = Property::factory()->for($team)->create();
    $account = createUtilityAccountFor($team, $property);

    $this
        ->actingAs($user)
        ->post(route('utility-bills.store', $team), [
            'utility_account_id' => $account->id,
            'invoice_number' => 'INV-2026-10',
            'billing_period_start' => '2026-09-01',
            'billing_period_end' => '2026-09-30',
            'issue_date' => '2026-10-01',
            'due_date' => '2026-10-15',
            'amount' => '123,45',
            'currency' => 'RON',
            'status' => 'unpaid',
            'paid_on' => null,
            'notes' => 'Factura lunară',
            'attachment' => UploadedFile::fake()->create(
                'factura-octombrie.pdf',
                120,
                'application/pdf',
            ),
        ])
        ->assertRedirect();

    $bill = UtilityBill::query()->firstOrFail();
    $document = Document::query()->firstOrFail();

    expect($bill)
        ->team_id->toBe($team->id)
        ->property_id->toBe($property->id)
        ->utility_account_id->toBe($account->id)
        ->amount_minor->toBe(12345)
        ->document_id->toBe($document->id);

    expect($document->category)->toBe(DocumentCategory::InvoiceReceipt);
    Storage::disk('local')->assertExists($document->path);
});

test('utility bill invoice number is unique per account and paid state requires paid date', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $property = Property::factory()->for($team)->create();
    $account = createUtilityAccountFor($team, $property);

    UtilityBill::query()->create([
        'team_id' => $team->id,
        'utility_account_id' => $account->id,
        'property_id' => $property->id,
        'created_by_user_id' => $user->id,
        'invoice_number' => 'INV-1',
        'billing_period_start' => '2026-09-01',
        'billing_period_end' => '2026-09-30',
        'issue_date' => '2026-10-01',
        'due_date' => '2026-10-15',
        'amount_minor' => 10000,
        'currency' => 'RON',
        'status' => 'unpaid',
    ]);

    $payload = [
        'utility_account_id' => $account->id,
        'invoice_number' => 'INV-1',
        'billing_period_start' => '2026-09-01',
        'billing_period_end' => '2026-09-30',
        'issue_date' => '2026-10-01',
        'due_date' => '2026-10-15',
        'amount' => '100.00',
        'currency' => 'RON',
        'status' => 'paid',
        'paid_on' => null,
    ];

    $this
        ->actingAs($user)
        ->post(route('utility-bills.store', $team), $payload)
        ->assertSessionHasErrors(['invoice_number', 'paid_on']);
});

test('utilities index is workspace scoped and calculates overdue summary', function () {
    Carbon::setTestNow('2026-10-20 12:00:00');

    $user = User::factory()->create();
    $team = $user->currentTeam;
    $otherTeam = Team::factory()->create();

    $property = Property::factory()->for($team)->create();
    $account = createUtilityAccountFor($team, $property);

    UtilityBill::query()->create([
        'team_id' => $team->id,
        'utility_account_id' => $account->id,
        'property_id' => $property->id,
        'created_by_user_id' => $user->id,
        'invoice_number' => 'LOCAL-1',
        'billing_period_start' => '2026-09-01',
        'billing_period_end' => '2026-09-30',
        'issue_date' => '2026-10-01',
        'due_date' => '2026-10-10',
        'amount_minor' => 25000,
        'currency' => 'RON',
        'status' => 'unpaid',
    ]);

    $otherProperty = Property::factory()->for($otherTeam)->create();
    createUtilityAccountFor($otherTeam, $otherProperty);

    $this
        ->actingAs($user)
        ->get(route('utilities.index', $team))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('utilities/index')
            ->has('accounts', 1)
            ->has('bills', 1)
            ->where('bills.0.invoice_number', 'LOCAL-1')
            ->where('bills.0.overdue', true)
            ->where('summary.active_accounts', 1)
            ->where('summary.unpaid_bills', 1)
            ->where('summary.overdue_bills', 1)
        );
});

test('utility actions cannot cross workspace boundaries', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $otherTeam = Team::factory()->create();
    $otherProperty = Property::factory()->for($otherTeam)->create();
    $account = createUtilityAccountFor($otherTeam, $otherProperty);

    $this
        ->actingAs($user)
        ->delete(route('utility-accounts.destroy', [$team, $account]))
        ->assertForbidden();

    $this->assertDatabaseHas('utility_accounts', ['id' => $account->id]);
});

test('utility account with bills must keep the bill history before deletion', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $property = Property::factory()->for($team)->create();
    $account = createUtilityAccountFor($team, $property);

    UtilityBill::query()->create([
        'team_id' => $team->id,
        'utility_account_id' => $account->id,
        'property_id' => $property->id,
        'created_by_user_id' => $user->id,
        'invoice_number' => 'KEEP-1',
        'billing_period_start' => '2026-09-01',
        'billing_period_end' => '2026-09-30',
        'issue_date' => '2026-10-01',
        'due_date' => '2026-10-15',
        'amount_minor' => 1000,
        'currency' => 'RON',
        'status' => 'unpaid',
    ]);

    $this
        ->actingAs($user)
        ->delete(route('utility-accounts.destroy', [$team, $account]))
        ->assertSessionHasErrors('utility_account');

    $this->assertDatabaseHas('utility_accounts', ['id' => $account->id]);
});

test('deleting a utility bill removes its dedicated attachment', function () {
    Storage::fake('local');

    $user = User::factory()->create();
    $team = $user->currentTeam;
    $property = Property::factory()->for($team)->create();
    $account = createUtilityAccountFor($team, $property);

    $document = Document::query()->create([
        'team_id' => $team->id,
        'property_id' => $property->id,
        'uploaded_by_user_id' => $user->id,
        'category' => DocumentCategory::InvoiceReceipt,
        'document_date' => '2026-10-01',
        'disk' => 'local',
        'path' => "documents/{$team->id}/bill.pdf",
        'original_name' => 'bill.pdf',
        'mime_type' => 'application/pdf',
        'size_bytes' => 50,
    ]);
    Storage::disk('local')->put($document->path, 'test');

    $bill = UtilityBill::query()->create([
        'team_id' => $team->id,
        'utility_account_id' => $account->id,
        'property_id' => $property->id,
        'document_id' => $document->id,
        'created_by_user_id' => $user->id,
        'invoice_number' => 'DELETE-1',
        'billing_period_start' => '2026-09-01',
        'billing_period_end' => '2026-09-30',
        'issue_date' => '2026-10-01',
        'due_date' => '2026-10-15',
        'amount_minor' => 1000,
        'currency' => 'RON',
        'status' => 'unpaid',
    ]);

    $this
        ->actingAs($user)
        ->delete(route('utility-bills.destroy', [$team, $bill]))
        ->assertRedirect();

    $this->assertDatabaseMissing('utility_bills', ['id' => $bill->id]);
    $this->assertDatabaseMissing('documents', ['id' => $document->id]);
    Storage::disk('local')->assertMissing($document->path);
});
