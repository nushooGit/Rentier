<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Lease;
use App\Models\Property;
use App\Models\Team;
use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('admin/dashboard', [
            'stats' => [
                'users' => User::query()->count(),
                'workspaces' => Team::query()->count(),
                'properties' => Property::query()->count(),
                'leases' => Lease::query()->count(),
            ],
            'recentUsers' => User::query()
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->limit(5)
                ->get(['id', 'name', 'email', 'created_at']),
            'recentWorkspaces' => Team::query()
                ->withCount(['members', 'properties'])
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->limit(5)
                ->get(['id', 'name', 'slug', 'is_personal', 'created_at']),
        ]);
    }
}
