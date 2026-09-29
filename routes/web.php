<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\LeaseController;
use App\Http\Controllers\PropertyController;
use App\Http\Controllers\RentPaymentController;
use App\Http\Controllers\Teams\TeamInvitationController;
use App\Http\Middleware\EnsureTeamMembership;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function (Request $request) {
    $appHost = parse_url((string) config('app.url'), PHP_URL_HOST);
    $isDedicatedAppHost = is_string($appHost)
        && str_starts_with($appHost, 'app.')
        && $request->getHost() === $appHost;

    if (! $isDedicatedAppHost) {
        return Inertia::render('welcome');
    }

    $user = $request->user();

    if (! $user) {
        return redirect()->route('login');
    }

    $team = $user->currentTeam ?? $user->personalTeam();

    return $team
        ? redirect()->route('dashboard', ['current_team' => $team->slug])
        : redirect()->route('teams.index');
})->name('home');

Route::prefix('{current_team}')
    ->middleware(['auth', 'verified', EnsureTeamMembership::class])
    ->group(function () {
        Route::get('dashboard', DashboardController::class)->name('dashboard');
        Route::resource('properties', PropertyController::class);
        Route::resource('leases', LeaseController::class);
        Route::resource('payments', RentPaymentController::class);
        Route::patch('expenses/{expense}/mark-reimbursed', [ExpenseController::class, 'markReimbursed'])->name('expenses.mark-reimbursed');
        Route::patch('expenses/{expense}/mark-recovered', [ExpenseController::class, 'markRecovered'])->name('expenses.mark-recovered');
        Route::patch('expenses/{expense}/undo-reimbursed', [ExpenseController::class, 'undoReimbursed'])->name('expenses.undo-reimbursed');
        Route::patch('expenses/{expense}/undo-recovered', [ExpenseController::class, 'undoRecovered'])->name('expenses.undo-recovered');
        Route::resource('expenses', ExpenseController::class);
    });

Route::middleware(['auth'])->group(function () {
    Route::get('invitations/{invitation}/accept', [TeamInvitationController::class, 'accept'])->name('invitations.accept');
    Route::delete('invitations/{invitation}', [TeamInvitationController::class, 'decline'])->name('invitations.decline');
});

require __DIR__.'/settings.php';
