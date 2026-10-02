<?php

use App\Enums\DocumentCategory;
use App\Models\Document;
use App\Models\Expense;
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

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function utilityBillPayload(UtilityAccount $account, array $overrides = []): array
{
    return array_merge([
        'utility_account_id' => $account->id,
        'invoice_number' => 'INV-'.uniqid(),
        'billing_period_start' => '2026-09-01',
        'billing_period_end' => '2026-09-30',
        'issue_date' => '2026-10-01',
        'due_date' => '2026-10-15',
        'amount' => '208.85',
        'currency' => 'RON',
        'status' => 'unpaid',
        'paid_by' => null,
        'paid_on' => null,
        'notes' => null,
    ], $overrides);
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

test('utility bill validation errors are localized for invalid amount and attachment', function () {
    Storage::fake('local');
    app()->setLocale('ro');

    $user = User::factory()->create();
    $team = $user->currentTeam;
    $property = Property::factory()->for($team)->create();
    $account = createUtilityAccountFor($team, $property);

    $this
        ->actingAs($user)
        ->post(route('utility-bills.store', $team), [
            'utility_account_id' => $account->id,
            'invoice_number' => 'INV-INVALID',
            'billing_period_start' => '2026-09-01',
            'billing_period_end' => '2026-09-30',
            'issue_date' => '2026-10-01',
            'due_date' => '2026-10-15',
            'amount' => 'asd',
            'currency' => 'RON',
            'status' => 'unpaid',
            'paid_on' => null,
            'notes' => null,
            'attachment' => UploadedFile::fake()->create(
                'factura.txt',
                10,
                'text/plain',
            ),
        ])
        ->assertSessionHasErrors([
            'amount' => 'Suma trebuie să fie un număr valid, cu maximum 2 zecimale.',
            'attachment' => 'Factura trebuie să fie PDF, JPG, PNG sau WebP.',
        ]);
});

test('utility bill without a document can receive an attachment when edited', function () {
    Storage::fake('local');

    $user = User::factory()->create();
    $team = $user->currentTeam;
    $property = Property::factory()->for($team)->create();
    $account = createUtilityAccountFor($team, $property);

    $bill = UtilityBill::query()->create([
        'team_id' => $team->id,
        'utility_account_id' => $account->id,
        'property_id' => $property->id,
        'created_by_user_id' => $user->id,
        'invoice_number' => 'NO-FILE-1',
        'billing_period_start' => '2026-09-01',
        'billing_period_end' => '2026-09-30',
        'issue_date' => '2026-10-01',
        'due_date' => '2026-10-15',
        'amount_minor' => 12300,
        'currency' => 'RON',
        'status' => 'unpaid',
    ]);

    $this
        ->actingAs($user)
        ->post(route('utility-bills.update', [$team, $bill]), [
            '_method' => 'put',
            'utility_account_id' => $account->id,
            'invoice_number' => 'NO-FILE-1',
            'billing_period_start' => '2026-09-01',
            'billing_period_end' => '2026-09-30',
            'issue_date' => '2026-10-01',
            'due_date' => '2026-10-15',
            'amount' => '123.00',
            'currency' => 'RON',
            'status' => 'unpaid',
            'paid_on' => null,
            'notes' => null,
            'attachment' => UploadedFile::fake()->create(
                'added-later.pdf',
                20,
                'application/pdf',
            ),
        ])
        ->assertRedirect();

    $freshBill = $bill->fresh();

    expect($freshBill->document_id)->not->toBeNull();

    $document = $freshBill->document()->firstOrFail();

    expect($document->original_name)->toBe('added-later.pdf');
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
        'paid_by' => null,
        'paid_on' => null,
    ];

    $this
        ->actingAs($user)
        ->post(route('utility-bills.store', $team), $payload)
        ->assertSessionHasErrors(['invoice_number', 'paid_by', 'paid_on']);
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

test('updating a utility bill keeps its attachment metadata aligned with the selected account', function () {
    Storage::fake('local');

    $user = User::factory()->create();
    $team = $user->currentTeam;
    $firstProperty = Property::factory()->for($team)->create();
    $secondProperty = Property::factory()->for($team)->create();
    $firstAccount = createUtilityAccountFor($team, $firstProperty);
    $secondAccount = createUtilityAccountFor($team, $secondProperty);

    $document = Document::query()->create([
        'team_id' => $team->id,
        'property_id' => $firstProperty->id,
        'uploaded_by_user_id' => $user->id,
        'category' => DocumentCategory::InvoiceReceipt,
        'document_date' => '2026-10-01',
        'disk' => 'local',
        'path' => "documents/{$team->id}/move.pdf",
        'original_name' => 'move.pdf',
        'mime_type' => 'application/pdf',
        'size_bytes' => 50,
    ]);

    $bill = UtilityBill::query()->create([
        'team_id' => $team->id,
        'utility_account_id' => $firstAccount->id,
        'property_id' => $firstProperty->id,
        'document_id' => $document->id,
        'created_by_user_id' => $user->id,
        'invoice_number' => 'MOVE-1',
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
        ->put(route('utility-bills.update', [$team, $bill]), [
            'utility_account_id' => $secondAccount->id,
            'invoice_number' => 'MOVE-1',
            'billing_period_start' => '2026-09-01',
            'billing_period_end' => '2026-09-30',
            'issue_date' => '2026-10-02',
            'due_date' => '2026-10-16',
            'amount' => '10.00',
            'currency' => 'RON',
            'status' => 'paid',
            'paid_by' => 'owner',
            'paid_on' => '2026-10-10',
            'notes' => null,
        ])
        ->assertRedirect();

    expect($bill->fresh())
        ->utility_account_id->toBe($secondAccount->id)
        ->property_id->toBe($secondProperty->id)
        ->status->value->toBe('paid');

    $freshDocument = $document->fresh();

    expect($freshDocument)
        ->property_id->toBe($secondProperty->id)
        ->lease_id->toBeNull();

    expect($freshDocument->document_date->toDateString())->toBe('2026-10-02');
});

test('unpaid owner utility bill creates a pending expense that affects profit but not cash', function () {
    Carbon::setTestNow('2026-10-10 12:00:00');

    $user = User::factory()->create();
    $team = $user->currentTeam;
    $property = Property::factory()->for($team)->create([
        'monthly_rent_amount' => 0,
        'deposit_amount' => 0,
    ]);
    $account = createUtilityAccountFor($team, $property);

    $this
        ->actingAs($user)
        ->post(route('utility-bills.store', $team), utilityBillPayload($account, [
            'invoice_number' => 'OWNER-UNPAID-1',
            'amount' => '208.85',
        ]))
        ->assertRedirect();

    $bill = UtilityBill::query()->where('invoice_number', 'OWNER-UNPAID-1')->firstOrFail();
    $expense = Expense::query()->where('utility_bill_id', $bill->id)->firstOrFail();

    expect($expense)
        ->category->toBe('utilities')
        ->amount->toBe('208.85')
        ->expense_date->toDateString()->toBe('2026-10-01')
        ->paid_by->toBe('owner')
        ->responsible_party->toBe('owner')
        ->settlement_type->toBe('none')
        ->status->toBe('pending');

    $this
        ->actingAs($user)
        ->get(route('dashboard', $team))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('summary.current_month_expenses', '208.85')
            ->where('summary.current_month_profit', '-208.85')
            ->where('summary.operational_cash_result', '0.00')
        );
});

test('owner utility payment changes cash in the payment month without moving the expense month', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $property = Property::factory()->for($team)->create([
        'monthly_rent_amount' => 0,
        'deposit_amount' => 0,
    ]);
    $account = createUtilityAccountFor($team, $property);

    $this
        ->actingAs($user)
        ->post(route('utility-bills.store', $team), utilityBillPayload($account, [
            'invoice_number' => 'OWNER-CASH-1',
            'amount' => '208.85',
        ]))
        ->assertRedirect();

    $bill = UtilityBill::query()->where('invoice_number', 'OWNER-CASH-1')->firstOrFail();

    $this
        ->actingAs($user)
        ->put(route('utility-bills.update', [$team, $bill]), utilityBillPayload($account, [
            'invoice_number' => 'OWNER-CASH-1',
            'amount' => '208.85',
            'status' => 'paid',
            'paid_by' => 'owner',
            'paid_on' => '2026-11-05',
        ]))
        ->assertRedirect();

    $expense = Expense::query()->where('utility_bill_id', $bill->id)->firstOrFail();

    expect($bill->fresh())
        ->paid_by->value->toBe('owner')
        ->paid_on->toDateString()->toBe('2026-11-05');

    expect($expense)
        ->expense_date->toDateString()->toBe('2026-10-01')
        ->status->toBe('paid');

    Carbon::setTestNow('2026-11-10 12:00:00');

    $this
        ->actingAs($user)
        ->get(route('dashboard', $team))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('summary.current_month_expenses', '0.00')
            ->where('summary.operational_cash_result', '-208.85')
        );
});

test('owner paid renter utility bill is recoverable and does not reduce owner profit', function () {
    Carbon::setTestNow('2026-10-10 12:00:00');

    $user = User::factory()->create();
    $team = $user->currentTeam;
    $property = Property::factory()->for($team)->create([
        'monthly_rent_amount' => 0,
        'deposit_amount' => 0,
    ]);
    $lease = Lease::factory()->for($team)->create([
        'property_id' => $property->id,
        'start_date' => '2026-10-01',
        'end_date' => '2026-12-31',
        'monthly_rent_amount' => 0,
        'deposit_amount' => 0,
        'rent_due_day' => 15,
    ]);
    $account = createUtilityAccountFor($team, $property, $lease);

    $this
        ->actingAs($user)
        ->post(route('utility-bills.store', $team), utilityBillPayload($account, [
            'invoice_number' => 'RENTER-OWNER-PAID-1',
            'status' => 'paid',
            'paid_by' => 'owner',
            'paid_on' => '2026-10-05',
        ]))
        ->assertRedirect();

    $bill = UtilityBill::query()->where('invoice_number', 'RENTER-OWNER-PAID-1')->firstOrFail();
    $expense = Expense::query()->where('utility_bill_id', $bill->id)->firstOrFail();

    expect($expense)
        ->paid_by->toBe('owner')
        ->responsible_party->toBe('tenant')
        ->settlement_type->toBe('reimburse')
        ->status->toBe('reimbursable');

    $this
        ->actingAs($user)
        ->get(route('dashboard', $team))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('summary.current_month_expenses', '0.00')
            ->where('summary.current_month_profit', '0.00')
            ->where('summary.recoverable_expenses', '208.85')
            ->where('summary.operational_cash_result', '-208.85')
        );
});

test('renter paid renter utility bill stays outside owner profit cash and recoveries', function () {
    Carbon::setTestNow('2026-10-10 12:00:00');

    $user = User::factory()->create();
    $team = $user->currentTeam;
    $property = Property::factory()->for($team)->create([
        'monthly_rent_amount' => 0,
        'deposit_amount' => 0,
    ]);
    $lease = Lease::factory()->for($team)->create([
        'property_id' => $property->id,
        'start_date' => '2026-10-01',
        'end_date' => '2026-12-31',
        'monthly_rent_amount' => 0,
        'deposit_amount' => 0,
        'rent_due_day' => 15,
    ]);
    $account = createUtilityAccountFor($team, $property, $lease);

    $this
        ->actingAs($user)
        ->post(route('utility-bills.store', $team), utilityBillPayload($account, [
            'invoice_number' => 'RENTER-PAID-1',
            'status' => 'paid',
            'paid_by' => 'renter',
            'paid_on' => '2026-10-05',
        ]))
        ->assertRedirect();

    $bill = UtilityBill::query()->where('invoice_number', 'RENTER-PAID-1')->firstOrFail();
    $expense = Expense::query()->where('utility_bill_id', $bill->id)->firstOrFail();

    expect($expense)
        ->paid_by->toBe('tenant')
        ->responsible_party->toBe('tenant')
        ->settlement_type->toBe('none')
        ->status->toBe('paid');

    $this
        ->actingAs($user)
        ->get(route('dashboard', $team))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('summary.current_month_expenses', '0.00')
            ->where('summary.current_month_profit', '0.00')
            ->where('summary.recoverable_expenses', '0.00')
            ->where('summary.operational_cash_result', '0.00')
        );
});

test('renter paid owner utility bill becomes an owner reimbursement without immediate owner cash outflow', function () {
    Carbon::setTestNow('2026-10-10 12:00:00');

    $user = User::factory()->create();
    $team = $user->currentTeam;
    $property = Property::factory()->for($team)->create([
        'monthly_rent_amount' => 0,
        'deposit_amount' => 0,
    ]);
    $account = createUtilityAccountFor($team, $property);

    $this
        ->actingAs($user)
        ->post(route('utility-bills.store', $team), utilityBillPayload($account, [
            'invoice_number' => 'OWNER-RENTER-PAID-1',
            'status' => 'paid',
            'paid_by' => 'renter',
            'paid_on' => '2026-10-05',
        ]))
        ->assertRedirect();

    $bill = UtilityBill::query()->where('invoice_number', 'OWNER-RENTER-PAID-1')->firstOrFail();
    $expense = Expense::query()->where('utility_bill_id', $bill->id)->firstOrFail();

    expect($expense)
        ->paid_by->toBe('tenant')
        ->responsible_party->toBe('owner')
        ->settlement_type->toBe('reimburse')
        ->status->toBe('reimbursable');

    $this
        ->actingAs($user)
        ->get(route('dashboard', $team))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('summary.current_month_expenses', '208.85')
            ->where('summary.current_month_profit', '-208.85')
            ->where('summary.tenant_reimbursement_expenses', '208.85')
            ->where('summary.operational_cash_result', '0.00')
        );

    $this
        ->actingAs($user)
        ->patch(route('expenses.mark-reimbursed', [$team, $expense]))
        ->assertRedirect();

    $this
        ->actingAs($user)
        ->get(route('dashboard', $team))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('summary.tenant_reimbursement_expenses', '0.00')
            ->where('summary.operational_cash_result', '-208.85')
        );
});

test('utility bill edits update one linked expense and deleting the bill removes it', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $property = Property::factory()->for($team)->create();
    $account = createUtilityAccountFor($team, $property);

    $this
        ->actingAs($user)
        ->post(route('utility-bills.store', $team), utilityBillPayload($account, [
            'invoice_number' => 'SYNC-1',
            'amount' => '100.00',
        ]))
        ->assertRedirect();

    $bill = UtilityBill::query()->where('invoice_number', 'SYNC-1')->firstOrFail();
    $expense = Expense::query()->where('utility_bill_id', $bill->id)->firstOrFail();
    $expenseId = $expense->id;

    $this
        ->actingAs($user)
        ->put(route('utility-bills.update', [$team, $bill]), utilityBillPayload($account, [
            'invoice_number' => 'SYNC-1-EDITED',
            'issue_date' => '2026-10-02',
            'amount' => '150.25',
            'status' => 'paid',
            'paid_by' => 'owner',
            'paid_on' => '2026-10-08',
        ]))
        ->assertRedirect();

    $freshExpense = Expense::query()->where('utility_bill_id', $bill->id)->firstOrFail();

    expect($freshExpense)
        ->id->toBe($expenseId)
        ->title->toContain('SYNC-1-EDITED')
        ->amount->toBe('150.25')
        ->expense_date->toDateString()->toBe('2026-10-02')
        ->status->toBe('paid');

    $this
        ->actingAs($user)
        ->delete(route('utility-bills.destroy', [$team, $bill]))
        ->assertRedirect();

    $this->assertDatabaseMissing('expenses', ['id' => $expenseId]);
});

test('utility managed expenses cannot be edited or deleted outside the utility bill flow', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $property = Property::factory()->for($team)->create();
    $account = createUtilityAccountFor($team, $property);

    $this
        ->actingAs($user)
        ->post(route('utility-bills.store', $team), utilityBillPayload($account, [
            'invoice_number' => 'MANAGED-1',
        ]))
        ->assertRedirect();

    $bill = UtilityBill::query()->where('invoice_number', 'MANAGED-1')->firstOrFail();
    $expense = Expense::query()->where('utility_bill_id', $bill->id)->firstOrFail();

    $this
        ->actingAs($user)
        ->get(route('expenses.edit', [$team, $expense]))
        ->assertStatus(409);

    $this
        ->actingAs($user)
        ->delete(route('expenses.destroy', [$team, $expense]))
        ->assertStatus(409);

    $this->assertDatabaseHas('expenses', ['id' => $expense->id]);
});

test('settled cross party utility expense stays settled on non financial bill edits and reopens on amount change', function () {
    Carbon::setTestNow('2026-10-10 12:00:00');

    $user = User::factory()->create();
    $team = $user->currentTeam;
    $property = Property::factory()->for($team)->create();
    $account = createUtilityAccountFor($team, $property);

    $this
        ->actingAs($user)
        ->post(route('utility-bills.store', $team), utilityBillPayload($account, [
            'invoice_number' => 'SETTLED-SYNC-1',
            'status' => 'paid',
            'paid_by' => 'renter',
            'paid_on' => '2026-10-05',
        ]))
        ->assertRedirect();

    $bill = UtilityBill::query()->where('invoice_number', 'SETTLED-SYNC-1')->firstOrFail();
    $expense = Expense::query()->where('utility_bill_id', $bill->id)->firstOrFail();

    $this
        ->actingAs($user)
        ->patch(route('expenses.mark-reimbursed', [$team, $expense]))
        ->assertRedirect();

    $settledAt = $expense->fresh()->settled_at;

    $this
        ->actingAs($user)
        ->put(route('utility-bills.update', [$team, $bill]), utilityBillPayload($account, [
            'invoice_number' => 'SETTLED-SYNC-1',
            'status' => 'paid',
            'paid_by' => 'renter',
            'paid_on' => '2026-10-05',
            'notes' => 'Notă actualizată',
        ]))
        ->assertRedirect();

    expect($expense->fresh())
        ->status->toBe('paid')
        ->settled_at->not->toBeNull()
        ->settled_at->toDateTimeString()->toBe($settledAt?->toDateTimeString());

    $this
        ->actingAs($user)
        ->put(route('utility-bills.update', [$team, $bill]), utilityBillPayload($account, [
            'invoice_number' => 'SETTLED-SYNC-1',
            'amount' => '250.00',
            'status' => 'paid',
            'paid_by' => 'renter',
            'paid_on' => '2026-10-05',
        ]))
        ->assertRedirect();

    expect($expense->fresh())
        ->status->toBe('reimbursable')
        ->settled_at->toBeNull();
});

