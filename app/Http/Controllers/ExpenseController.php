<?php

namespace App\Http\Controllers;

use App\Http\Requests\Expenses\SaveExpenseRequest;
use App\Models\Expense;
use App\Models\Lease;
use App\Models\Property;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ExpenseController extends Controller
{
    /**
     * Display a listing of expenses for the current workspace.
     */
    public function index(Request $request, Team $currentTeam): Response
    {
        Gate::authorize('viewAny', [Expense::class, $currentTeam]);

        $expenses = Expense::query()
            ->with(['property', 'lease.renter'])
            ->whereBelongsTo($currentTeam)
            ->latest('expense_date')
            ->latest()
            ->get()
            ->map(fn (Expense $expense) => $this->serializeExpense($expense));

        return Inertia::render('expenses/index', [
            'expenses' => $expenses,
            'expenseCategories' => $this->expenseCategories(),
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
            'expense' => $this->serializeExpense($expense->load(['property', 'lease.renter'])),
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
            'expense' => $this->serializeExpense($expense->load(['property', 'lease.renter'])),
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

    /**
     * @return array<int, array{value: string, label: string}>
     */
    private function expenseCategories(): array
    {
        return [
            ['value' => 'maintenance', 'label' => 'Maintenance'],
            ['value' => 'utilities', 'label' => 'Utilities'],
            ['value' => 'taxes', 'label' => 'Taxes'],
            ['value' => 'insurance', 'label' => 'Insurance'],
            ['value' => 'admin', 'label' => 'Admin'],
            ['value' => 'repairs', 'label' => 'Repairs'],
            ['value' => 'other', 'label' => 'Other'],
        ];
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
     * @return array<int, array{id: int, property_id: int, label: string}>
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
            ])
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeExpense(Expense $expense): array
    {
        return [
            'id' => $expense->id,
            'team_id' => $expense->team_id,
            'property_id' => $expense->property_id,
            'lease_id' => $expense->lease_id,
            'title' => $expense->title,
            'category' => $expense->category,
            'amount' => $expense->amount,
            'currency' => $expense->currency,
            'expense_date' => $expense->expense_date->toDateString(),
            'paid_by' => $expense->paid_by,
            'responsible_party' => $expense->responsible_party,
            'settlement_type' => $expense->settlement_type,
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

    private function abortIfExpenseIsOutsideWorkspace(Team $currentTeam, Expense $expense): void
    {
        abort_unless($expense->team_id === $currentTeam->id, 404);
    }
}
