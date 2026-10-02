<?php

namespace App\Repositories;

use App\Models\Team;
use Illuminate\Database\Eloquent\Collection;

class OutletRepository
{
    /**
     * @return Collection<int, Team>
     */
    public function allWithMemberCount(): Collection
    {
        return Team::query()->withCount('members')->orderBy('name')->get();
    }

    public function count(): int
    {
        return Team::count();
    }

    public function create(string $name): Team
    {
        return Team::create(['name' => $name]);
    }

    public function update(Team $outlet, string $name): Team
    {
        $outlet->update(['name' => $name]);

        return $outlet;
    }

    public function hasMembers(Team $outlet): bool
    {
        return $outlet->memberships()->exists();
    }

    public function delete(Team $outlet): void
    {
        $outlet->delete();
    }
}
