<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\PlatformAdmin;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function __invoke(): Response
    {
        $users = User::query()
            ->withCount('teams')
            ->latest()
            ->limit(200)
            ->get()
            ->map(fn (User $user): array => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'email_verified_at' => $user->email_verified_at,
                'created_at' => $user->created_at,
                'workspaces_count' => (int) $user->getAttribute('teams_count'),
                'is_platform_admin' => PlatformAdmin::allows($user),
            ]);

        /** @var Collection<int, array<string, mixed>> $users */
        return Inertia::render('admin/users/index', [
            'users' => $users,
        ]);
    }
}
