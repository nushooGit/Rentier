<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Team;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class WorkspaceController extends Controller
{
    public function __invoke(): Response
    {
        $workspaces = Team::query()
            ->withCount(['members', 'properties', 'leases'])
            ->latest()
            ->limit(200)
            ->get()
            ->map(fn (Team $team): array => [
                'id' => $team->id,
                'name' => $team->name,
                'slug' => $team->slug,
                'is_personal' => $team->is_personal,
                'created_at' => $team->created_at,
                'members_count' => (int) $team->getAttribute('members_count'),
                'properties_count' => (int) $team->getAttribute('properties_count'),
                'leases_count' => (int) $team->getAttribute('leases_count'),
            ]);

        /** @var Collection<int, array<string, mixed>> $workspaces */
        return Inertia::render('admin/workspaces/index', [
            'workspaces' => $workspaces,
        ]);
    }
}
