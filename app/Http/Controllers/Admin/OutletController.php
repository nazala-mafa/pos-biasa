<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Teams\AssignDefaultTeam;
use App\Http\Controllers\Controller;
use App\Http\Requests\Outlet\StoreOutletRequest;
use App\Http\Requests\Outlet\UpdateOutletRequest;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class OutletController extends Controller
{
    /**
     * Display a listing of outlets.
     */
    public function index(): Response
    {
        return Inertia::render('admin/outlets/index', [
            'outlets' => Team::query()
                ->where('is_personal', false)
                ->withCount('members')
                ->orderBy('name')
                ->get()
                ->map(fn (Team $outlet) => [
                    'slug' => $outlet->slug,
                    'name' => $outlet->name,
                    'membersCount' => $outlet->members_count,
                    'isDefault' => $this->isDefault($outlet),
                    'createdAt' => $outlet->created_at?->toDateString(),
                ]),
        ]);
    }

    /**
     * Show the form for creating an outlet.
     */
    public function create(): Response
    {
        return Inertia::render('admin/outlets/form', ['outlet' => null]);
    }

    /**
     * Store a newly created outlet.
     */
    public function store(StoreOutletRequest $request): RedirectResponse
    {
        Team::create(['name' => $request->validated('name'), 'is_personal' => false]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Outlet created.')]);

        return to_route('admin.outlets.index');
    }

    /**
     * Show the form for editing an outlet.
     */
    public function edit(Team $outlet): Response
    {
        abort_if($outlet->is_personal, 404);

        return Inertia::render('admin/outlets/form', [
            'outlet' => [
                'slug' => $outlet->slug,
                'name' => $outlet->name,
                'isDefault' => $this->isDefault($outlet),
            ],
        ]);
    }

    /**
     * Update the specified outlet.
     */
    public function update(UpdateOutletRequest $request, Team $outlet): RedirectResponse
    {
        abort_if($outlet->is_personal, 404);
        abort_if($this->isDefault($outlet), 403, __('The default outlet cannot be modified.'));

        $outlet->update(['name' => $request->validated('name')]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Outlet updated.')]);

        return to_route('admin.outlets.index');
    }

    /**
     * Remove the specified outlet.
     */
    public function destroy(Team $outlet): RedirectResponse
    {
        abort_if($outlet->is_personal, 404);
        abort_if($this->isDefault($outlet), 403, __('The default outlet cannot be deleted.'));

        if ($outlet->memberships()->exists()) {
            Inertia::flash('toast', ['type' => 'error', 'message' => __('Move this outlet\'s users elsewhere before deleting it.')]);

            return to_route('admin.outlets.index');
        }

        $outlet->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Outlet deleted.')]);

        return to_route('admin.outlets.index');
    }

    private function isDefault(Team $outlet): bool
    {
        return $outlet->name === AssignDefaultTeam::NAME;
    }
}
