<?php

use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\UserShowController as AdminUserShowController;
use App\Http\Controllers\Admin\UserSuspensionController as AdminUserSuspensionController;
use App\Http\Controllers\Admin\WorkspaceController as AdminWorkspaceController;
use App\Http\Controllers\Admin\WorkspaceShowController as AdminWorkspaceShowController;
use App\Http\Controllers\Admin\WorkspaceSuspensionController as AdminWorkspaceSuspensionController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\LeaseController;
use App\Http\Controllers\PropertyController;
use App\Http\Controllers\ReminderController;
use App\Http\Controllers\RentPaymentController;
use App\Http\Controllers\Teams\TeamInvitationController;
use App\Http\Middleware\EnsurePlatformAdmin;
use App\Http\Middleware\EnsureTeamMembership;
use App\Http\Middleware\RejectAdminHost;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

$adminHost = parse_url((string) config('rentier.admin_url'), PHP_URL_HOST);

if (is_string($adminHost) && $adminHost !== '') {
    Route::domain($adminHost)
        ->middleware(EnsurePlatformAdmin::class)
        ->name('admin.')
        ->group(function () {
            Route::get('/', AdminDashboardController::class)->name('dashboard');
            Route::get('users', AdminUserController::class)->name('users.index');
            Route::get('users/{user}', AdminUserShowController::class)->name('users.show');
            Route::patch('users/{user}/suspend', [AdminUserSuspensionController::class, 'suspend'])->name('users.suspend');
            Route::patch('users/{user}/reactivate', [AdminUserSuspensionController::class, 'reactivate'])->name('users.reactivate');
            Route::get('workspaces', AdminWorkspaceController::class)->name('workspaces.index');
            Route::get('workspaces/{workspace}', AdminWorkspaceShowController::class)->name('workspaces.show');
            Route::patch('workspaces/{workspace}/suspend', [AdminWorkspaceSuspensionController::class, 'suspend'])->name('workspaces.suspend');
            Route::patch('workspaces/{workspace}/reactivate', [AdminWorkspaceSuspensionController::class, 'reactivate'])->name('workspaces.reactivate');
        });
}

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

    $team = $user->currentTeam;

    if (! $team || $team->isSuspended()) {
        $team = $user->fallbackTeam();
    }

    return $team
        ? redirect()->route('dashboard', ['current_team' => $team->slug])
        : redirect()->route('teams.index');
})->name('home');

Route::prefix('{current_team}')
    ->middleware(['auth', 'verified', RejectAdminHost::class, EnsureTeamMembership::class])
    ->group(function () {
        Route::get('dashboard', DashboardController::class)->name('dashboard');
        Route::get('calendar', CalendarController::class)->name('calendar.index');
        Route::post('reminders', [ReminderController::class, 'store'])->name('reminders.store');
        Route::put('reminders/{reminder}', [ReminderController::class, 'update'])->name('reminders.update');
        Route::patch('reminders/{reminder}/toggle-complete', [ReminderController::class, 'toggleComplete'])->name('reminders.toggle-complete');
        Route::delete('reminders/{reminder}', [ReminderController::class, 'destroy'])->name('reminders.destroy');
        Route::resource('properties', PropertyController::class);
        Route::resource('leases', LeaseController::class);
        Route::resource('payments', RentPaymentController::class);
        Route::patch('expenses/{expense}/mark-reimbursed', [ExpenseController::class, 'markReimbursed'])->name('expenses.mark-reimbursed');
        Route::patch('expenses/{expense}/mark-recovered', [ExpenseController::class, 'markRecovered'])->name('expenses.mark-recovered');
        Route::patch('expenses/{expense}/undo-reimbursed', [ExpenseController::class, 'undoReimbursed'])->name('expenses.undo-reimbursed');
        Route::patch('expenses/{expense}/undo-recovered', [ExpenseController::class, 'undoRecovered'])->name('expenses.undo-recovered');
        Route::resource('expenses', ExpenseController::class);
        Route::get('documents/{document}/download', [DocumentController::class, 'download'])->name('documents.download');
        Route::resource('documents', DocumentController::class)->only(['index', 'store', 'destroy']);
    });

Route::middleware(['auth', RejectAdminHost::class])->group(function () {
    Route::get('invitations/{invitation}/accept', [TeamInvitationController::class, 'accept'])->name('invitations.accept');
    Route::delete('invitations/{invitation}', [TeamInvitationController::class, 'decline'])->name('invitations.decline');
});

require __DIR__.'/settings.php';
