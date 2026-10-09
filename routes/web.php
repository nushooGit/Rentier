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
use App\Http\Controllers\ExportController;
use App\Http\Controllers\LeaseController;
use App\Http\Controllers\PropertyController;
use App\Http\Controllers\ReminderController;
use App\Http\Controllers\RentPaymentController;
use App\Http\Controllers\Renters\RenterInvitationController;
use App\Http\Controllers\Renters\RenterInvitationAccessController;
use App\Http\Controllers\Renters\RenterPortalController;
use App\Http\Controllers\UtilityAccountController;
use App\Http\Controllers\UtilityBillController;
use App\Http\Controllers\UtilityBillReaderController;
use App\Http\Controllers\UtilityController;
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
        Route::get('utilities', [UtilityController::class, 'index'])->name('utilities.index');
        Route::post('utility-accounts', [UtilityAccountController::class, 'store'])->name('utility-accounts.store');
        Route::put('utility-accounts/{utility_account}', [UtilityAccountController::class, 'update'])->name('utility-accounts.update');
        Route::delete('utility-accounts/{utility_account}', [UtilityAccountController::class, 'destroy'])->name('utility-accounts.destroy');
        Route::post('utility-bills', [UtilityBillController::class, 'store'])->name('utility-bills.store');
        Route::post('utility-bills/analyze', UtilityBillReaderController::class)->name('utility-bills.analyze');
        Route::put('utility-bills/{utility_bill}', [UtilityBillController::class, 'update'])->name('utility-bills.update');
        Route::delete('utility-bills/{utility_bill}', [UtilityBillController::class, 'destroy'])->name('utility-bills.destroy');
        Route::post('reminders', [ReminderController::class, 'store'])->name('reminders.store');
        Route::put('reminders/{reminder}', [ReminderController::class, 'update'])->name('reminders.update');
        Route::patch('reminders/{reminder}/toggle-complete', [ReminderController::class, 'toggleComplete'])->name('reminders.toggle-complete');
        Route::delete('reminders/{reminder}', [ReminderController::class, 'destroy'])->name('reminders.destroy');
        Route::resource('properties', PropertyController::class);
        Route::resource('leases', LeaseController::class);
        Route::post('renters/{renter}/invitations', [RenterInvitationController::class, 'store'])->name('renters.invitations.store');
        Route::delete('renter-invitations/{invitation}', [RenterInvitationController::class, 'destroy'])->name('renters.invitations.destroy');
        Route::delete('renters/{renter}/portal-access', [RenterInvitationController::class, 'revokeAccess'])->name('renters.portal-access.destroy');
        Route::resource('payments', RentPaymentController::class);
        Route::patch('expenses/{expense}/mark-reimbursed', [ExpenseController::class, 'markReimbursed'])->name('expenses.mark-reimbursed');
        Route::patch('expenses/{expense}/mark-recovered', [ExpenseController::class, 'markRecovered'])->name('expenses.mark-recovered');
        Route::patch('expenses/{expense}/undo-reimbursed', [ExpenseController::class, 'undoReimbursed'])->name('expenses.undo-reimbursed');
        Route::patch('expenses/{expense}/undo-recovered', [ExpenseController::class, 'undoRecovered'])->name('expenses.undo-recovered');
        Route::resource('expenses', ExpenseController::class);
        Route::get('exports', [ExportController::class, 'index'])->name('exports.index');
        Route::get('exports/{dataset}', [ExportController::class, 'download'])->name('exports.download');
        Route::get('documents/{document}/download', [DocumentController::class, 'download'])->name('documents.download');
        Route::resource('documents', DocumentController::class)->only(['index', 'store', 'destroy']);
    });

Route::middleware([RejectAdminHost::class, 'throttle:20,1'])->get('renter-invitations/{token}', [RenterInvitationAccessController::class, 'show'])->name('renter-invitations.show');
Route::middleware([RejectAdminHost::class, 'throttle:5,1'])->post('renter-invitations/{token}/register', [RenterInvitationAccessController::class, 'register'])->name('renter-invitations.register');

Route::middleware(['auth', 'verified', RejectAdminHost::class])->get('renter/overview', RenterPortalController::class)->name('renter.overview');

Route::middleware(['auth', 'verified', RejectAdminHost::class])->post('renter-invitations/accept', [RenterInvitationController::class, 'accept'])->middleware('throttle:6,1')->name('renter-invitations.accept');

Route::middleware(['auth', RejectAdminHost::class])->group(function () {
    Route::get('invitations/{invitation}/accept', [TeamInvitationController::class, 'accept'])->name('invitations.accept');
    Route::delete('invitations/{invitation}', [TeamInvitationController::class, 'decline'])->name('invitations.decline');
});

require __DIR__.'/settings.php';
