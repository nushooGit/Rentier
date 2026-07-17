<?php

namespace App\Http\Controllers;

use App\Http\Requests\Expenses\SaveExpenseRequest;
use App\Models\Expense;
use App\Models\Lease;
use App\Models\Property;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class ExpenseController extends Controller
{
    /**
     * @var array<string, string>
     */
    private const EXPENSE_CATEGORIES = [
        'repairs' => 'Reparații',
        'maintenance' => 'Întreținere',
        'utilities' => 'Utilități',
        'renovation' => 'Zugrăvit / renovări',
        'taxes' => 'Taxe',
        'other' => 'Altele',
    ];

    /**
     * Display a listing of expenses for the current workspace.
     */
    public function index(Request $request, Team $currentTeam): Response
    {
        Gate::authorize('viewAny', [Expense::class, $currentTeam]);

        $selectedCategory = $this->selectedCategory($request->query('category'));

        $expenseQuery = Expense::query()
            ->with(['property', 'lease.renter'])
            ->whereBelongsTo($currentTeam);

        if ($selectedCategory !== null) {
            $expenseQuery->where(function ($query) use ($selectedCategory) {
                if ($selectedCategory === 'other') {
                    $query
                        ->whereIn('category', ['other', 'insurance', 'admin'])
                        ->orWhereNull('category')
                        ->orWhere('category', '');

                    return;
                }

                $query->where('category', $selectedCategory);
            });
        }

        $expenseModels = $expenseQuery
            ->latest('expense_date')
            ->latest()
            ->get();

        $expenses = $expenseModels
            ->map(fn (Expense $expense) => $this->serializeExpense($expense, $currentTeam));

        return Inertia::render('expenses/index', [
            'expenses' => $expenses,
            'expenseCategories' => $this->expenseCategories(),
            'filters' => [
                'category' => $selectedCategory,
            ],
            'summary' => $this->expenseSummary($expenseModels),
            'expensePaidByOptions' => $this->paidByOptions(),
            'expenseResponsiblePartyOptions' => $this->responsiblePartyOptions(),
            'expenseSettlementTypeOptions' => $this->settlementTypeOptions(),
            'expenseStatuses' => $this->expenseStatuses(),
        ]);
    }

    /**
     * Show the form for creating a new expense.
     */
    public function create(Team $currentTeam): Response
    {
        Gate::authorize('create', [Expense::class, $currentTeam]);

        return Inertia::render('expenses/create', [
            'properties' => $this->propertyOptions($currentTeam),
            'leases' => $this->leaseOptions($currentTeam),
            'expenseCategories' => $this->expenseCategories(),
            'expensePaidByOptions' => $this->paidByOptions(),
            'expenseResponsiblePartyOptions' => $this->responsiblePartyOptions(),
            'expenseSettlementTypeOptions' => $this->settlementTypeOptions(),
            'expenseStatuses' => $this->expenseStatuses(),
        ]);
    }

    /**
     * Store a newly created expense.
     */
    public function store(SaveExpenseRequest $request, Team $currentTeam): RedirectResponse
    {
        Expense::create([
            ...$request->validatedWithDefaults(),
            'team_id' => $currentTeam->id,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Expense created.')]);

        return to_route('expenses.index', ['current_team' => $currentTeam]);
    }

    /**
     * Display the expense details.
     */
    public function show(Team $currentTeam, Expense $expense): Response
    {
        Gate::authorize('view', $expense);
        $this->abortIfExpenseIsOutsideWorkspace($currentTeam, $expense);

        return Inertia::render('expenses/show', [
            'expense' => $this->serializeExpense($expense->load(['property', 'lease.renter']), $currentTeam),
        ]);
    }

    /**
     * Show the form for editing the expense.
     */
    public function edit(Team $currentTeam, Expense $expense): Response
    {
        Gate::authorize('update', $expense);
        $this->abortIfExpenseIsOutsideWorkspace($currentTeam, $expense);

        return Inertia::render('expenses/edit', [
            'expense' => $this->serializeExpense($expense->load(['property', 'lease.renter']), $currentTeam),
            'properties' => $this->propertyOptions($currentTeam),
            'leases' => $this->leaseOptions($currentTeam),
            'expenseCategories' => $this->expenseCategories(),
            'expensePaidByOptions' => $this->paidByOptions(),
            'expenseResponsiblePartyOptions' => $this->responsiblePartyOptions(),
            'expenseSettlementTypeOptions' => $this->settlementTypeOptions(),
            'expenseStatuses' => $this->expenseStatuses(),
        ]);
    }

    /**
     * Update the expense.
     */
    public function update(SaveExpenseRequest $request, Team $currentTeam, Expense $expense): RedirectResponse
    {
        $this->abortIfExpenseIsOutsideWorkspace($currentTeam, $expense);

        $expense->update($request->validatedWithDefaults());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Expense updated.')]);

        return to_route('expenses.show', [
            'current_team' => $currentTeam,
            'expense' => $expense,
        ]);
    }

    /**
     * Remove the expense.
     */
    public function destroy(Team $currentTeam, Expense $expense): RedirectResponse
    {
        Gate::authorize('delete', $expense);
        $this->abortIfExpenseIsOutsideWorkspace($currentTeam, $expense);

        $expense->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Expense deleted.')]);

        return to_route('expenses.index', ['current_team' => $currentTeam]);
    }

    public function markReimbursed(Team $currentTeam, Expense $expense): RedirectResponse
    {
        Gate::authorize('update', $expense);
        $this->abortIfExpenseIsOutsideWorkspace($currentTeam, $expense);

        if (! $expense->requiresOwnerReimbursement()) {
            throw ValidationException::withMessages([
                'expense' => 'Această cheltuială nu poate fi marcată ca rambursată.',
            ]);
        }

        if ($expense->settled_at !== null) {
            throw ValidationException::withMessages([
                'expense' => 'Această cheltuială este deja închisă.',
            ]);
        }

        $expense->update([
            'settled_at' => now(),
            'status' => 'paid',
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Rambursarea a fost marcată ca efectuată.']);

        return back();
    }

    public function markRecovered(Team $currentTeam, Expense $expense): RedirectResponse
    {
        Gate::authorize('update', $expense);
        $this->abortIfExpenseIsOutsideWorkspace($currentTeam, $expense);

        if (! $expense->requiresTenantRecovery()) {
            throw ValidationException::withMessages([
                'expense' => 'Această cheltuială nu poate fi marcată ca recuperată.',
            ]);
        }

        if ($expense->settled_at !== null) {
            throw ValidationException::withMessages([
                'expense' => 'Această cheltuială este deja închisă.',
            ]);
        }

        $expense->update([
            'settled_at' => now(),
            'status' => 'paid',
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Suma a fost marcată ca recuperată.']);

        return back();
    }

    public function undoReimbursed(Team $currentTeam, Expense $expense): RedirectResponse
    {
        Gate::authorize('update', $expense);
        $this->abortIfExpenseIsOutsideWorkspace($currentTeam, $expense);

        if (! $expense->requiresOwnerReimbursement()) {
            throw ValidationException::withMessages([
                'expense' => 'Această acțiune nu este permisă pentru această cheltuială.',
            ]);
        }

        if ($expense->settled_at === null) {
            throw ValidationException::withMessages([
                'expense' => 'Această cheltuială nu este închisă.',
            ]);
        }

        $expense->update([
            'settled_at' => null,
            'status' => 'reimbursable',
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Rambursarea a fost anulată.']);

        return back();
    }

    public function undoRecovered(Team $currentTeam, Expense $expense): RedirectResponse
    {
        Gate::authorize('update', $expense);
        $this->abortIfExpenseIsOutsideWorkspace($currentTeam, $expense);

        if (! $expense->requiresTenantRecovery()) {
            throw ValidationException::withMessages([
                'expense' => 'Această acțiune nu este permisă pentru această cheltuială.',
            ]);
        }

        if ($expense->settled_at === null) {
            throw ValidationException::withMessages([
                'expense' => 'Această cheltuială nu este închisă.',
            ]);
        }

        $expense->update([
            'settled_at' => null,
            'status' => 'reimbursable',
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Recuperarea a fost anulată.']);

        return back();
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    private function expenseCategories(): array
    {
        return collect(self::EXPENSE_CATEGORIES)
            ->map(fn (string $label, string $value) => ['value' => $value, 'label' => $label])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    private function paidByOptions(): array
    {
        return [
            ['value' => 'owner', 'label' => 'Owner'],
            ['value' => 'tenant', 'label' => 'Tenant'],
        ];
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    private function responsiblePartyOptions(): array
    {
        return [
            ['value' => 'owner', 'label' => 'Owner'],
            ['value' => 'tenant', 'label' => 'Tenant'],
        ];
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    private function settlementTypeOptions(): array
    {
        return [
            ['value' => 'none', 'label' => 'None'],
            ['value' => 'deduct_from_rent', 'label' => 'Deduct from rent'],
            ['value' => 'deduct_from_utilities', 'label' => 'Deduct from utilities'],
            ['value' => 'reimburse', 'label' => 'Reimburse'],
        ];
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    private function expenseStatuses(): array
    {
        return [
            ['value' => 'paid', 'label' => 'Paid'],
            ['value' => 'pending', 'label' => 'Pending'],
            ['value' => 'reimbursable', 'label' => 'Reimbursable'],
            ['value' => 'cancelled', 'label' => 'Cancelled'],
        ];
    }

    /**
     * @return array<int, array{id: int, name: string, city: string}>
     */
    private function propertyOptions(Team $currentTeam): array
    {
        return Property::query()
            ->whereBelongsTo($currentTeam)
            ->orderBy('name')
            ->get(['id', 'name', 'city'])
            ->map(fn (Property $property) => [
                'id' => $property->id,
                'name' => $property->name,
                'city' => $property->city,
            ])
            ->all();
    }

    /**
     * @return array<int, array{id: int, property_id: int, label: string, start_date: string, end_date: string|null}>
     */
    private function leaseOptions(Team $currentTeam): array
    {
        return Lease::query()
            ->with(['property', 'renter'])
            ->whereBelongsTo($currentTeam)
            ->latest('start_date')
            ->get()
            ->map(fn (Lease $lease) => [
                'id' => $lease->id,
                'property_id' => $lease->property_id,
                'label' => $lease->renter->name.' · '.$lease->property->name,
                'start_date' => $lease->start_date->toDateString(),
                'end_date' => $lease->end_date?->toDateString(),
            ])
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeExpense(Expense $expense, Team $currentTeam): array
    {
        $settlementState = $this->settlementState($expense, $currentTeam);

        return [
            'id' => $expense->id,
            'team_id' => $expense->team_id,
            'property_id' => $expense->property_id,
            'lease_id' => $expense->lease_id,
            'title' => $expense->title,
            'category' => $this->normalizeExpenseCategory($expense->category),
            'amount' => $expense->amount,
            'currency' => $expense->currency,
            'expense_date' => $expense->expense_date->toDateString(),
            'paid_by' => $expense->paid_by,
            'responsible_party' => $expense->responsible_party,
            'settlement_type' => $expense->settlement_type,
            'settled_at' => $expense->settled_at?->toISOString(),
            'settlement_state' => $settlementState,
            'affects_owner_profit' => $expense->responsible_party === 'owner',
            'status' => $expense->status,
            'notes' => $expense->notes,
            'property' => [
                'id' => $expense->property->id,
                'name' => $expense->property->name,
                'city' => $expense->property->city,
            ],
            'lease' => $expense->lease ? [
                'id' => $expense->lease->id,
                'renter' => [
                    'id' => $expense->lease->renter->id,
                    'name' => $expense->lease->renter->name,
                ],
            ] : null,
            'created_at' => $expense->created_at?->toISOString(),
            'updated_at' => $expense->updated_at?->toISOString(),
        ];
    }

    /**
     * @return array{kind: string, label: string|null, action_label: string|null, action_route: string|null, settled_label: string|null}
     */
    private function settlementState(Expense $expense, Team $currentTeam): array
    {
        if ($expense->requiresOwnerReimbursement()) {
            return [
                'kind' => $expense->settled_at ? 'reimbursed' : 'reimbursement_due',
                'label' => $expense->settled_at ? 'Rambursat' : 'De rambursat',
                'action_label' => $expense->settled_at ? 'Anulează rambursarea' : 'Marchează ca rambursat',
                'action_route' => $expense->settled_at
                    ? route('expenses.undo-reimbursed', [$currentTeam, $expense], false)
                    : route('expenses.mark-reimbursed', [$currentTeam, $expense], false),
                'settled_label' => $expense->settled_at ? 'Rambursat la '.$this->romanianDate($expense->settled_at) : null,
            ];
        }

        if ($expense->requiresTenantRecovery()) {
            return [
                'kind' => $expense->settled_at ? 'recovered' : 'recovery_due',
                'label' => $expense->settled_at ? 'Recuperat' : 'De recuperat',
                'action_label' => $expense->settled_at ? 'Anulează recuperarea' : 'Marchează ca recuperat',
                'action_route' => $expense->settled_at
                    ? route('expenses.undo-recovered', [$currentTeam, $expense], false)
                    : route('expenses.mark-recovered', [$currentTeam, $expense], false),
                'settled_label' => $expense->settled_at ? 'Recuperat la '.$this->romanianDate($expense->settled_at) : null,
            ];
        }

        return [
            'kind' => 'none',
            'label' => null,
            'action_label' => null,
            'action_route' => null,
            'settled_label' => null,
        ];
    }

    private function abortIfExpenseIsOutsideWorkspace(Team $currentTeam, Expense $expense): void
    {
        abort_unless($expense->team_id === $currentTeam->id, 404);
    }

    private function romanianDate(\DateTimeInterface $date): string
    {
        $months = [
            1 => 'ianuarie',
            2 => 'februarie',
            3 => 'martie',
            4 => 'aprilie',
            5 => 'mai',
            6 => 'iunie',
            7 => 'iulie',
            8 => 'august',
            9 => 'septembrie',
            10 => 'octombrie',
            11 => 'noiembrie',
            12 => 'decembrie',
        ];

        return (int) $date->format('j').' '.$months[(int) $date->format('n')].' '.$date->format('Y');
    }

    private function selectedCategory(mixed $category): ?string
    {
        if (! is_string($category) || $category === '' || $category === 'all') {
            return null;
        }

        return array_key_exists($category, self::EXPENSE_CATEGORIES) ? $category : null;
    }

    private function normalizeExpenseCategory(?string $category): string
    {
        return is_string($category) && array_key_exists($category, self::EXPENSE_CATEGORIES)
            ? $category
            : 'other';
    }

    /**
     * @param  Collection<int, Expense>  $expenses
     * @return array{total: string, owner_supported: string, tenant_supported: string, owner_paid: string, tenant_paid: string, by_category: array<string, string>}
     */
    private function expenseSummary(Collection $expenses): array
    {
        $activeExpenses = $expenses->reject(fn (Expense $expense) => $expense->status === 'cancelled');
        $categoryTotals = collect(array_keys(self::EXPENSE_CATEGORIES))
            ->mapWithKeys(fn (string $category) => [$category => 0.0])
            ->all();

        foreach ($activeExpenses as $expense) {
            $category = $this->normalizeExpenseCategory($expense->category);
            $categoryTotals[$category] += (float) $expense->amount;
        }

        return [
            'total' => $this->decimalString($activeExpenses->sum(fn (Expense $expense) => (float) $expense->amount)),
            'owner_supported' => $this->decimalString($activeExpenses
                ->where('responsible_party', 'owner')
                ->sum(fn (Expense $expense) => (float) $expense->amount)),
            'tenant_supported' => $this->decimalString($activeExpenses
                ->where('responsible_party', 'tenant')
                ->sum(fn (Expense $expense) => (float) $expense->amount)),
            'owner_paid' => $this->decimalString($activeExpenses
                ->where('paid_by', 'owner')
                ->sum(fn (Expense $expense) => (float) $expense->amount)),
            'tenant_paid' => $this->decimalString($activeExpenses
                ->where('paid_by', 'tenant')
                ->sum(fn (Expense $expense) => (float) $expense->amount)),
            'by_category' => collect($categoryTotals)
                ->map(fn (float $amount) => $this->decimalString($amount))
                ->all(),
        ];
    }

    private function decimalString(float|int|string $amount): string
    {
        return number_format((float) $amount, 2, '.', '');
    }
}
