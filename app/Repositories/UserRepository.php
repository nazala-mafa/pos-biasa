<?php

namespace App\Repositories;

use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class UserRepository
{
    /**
     * Non-superadmin members of the outlet, with their roles.
     *
     * @return Collection<int, User>
     */
    public function membersOf(Team $outlet): Collection
    {
        return $this->memberQuery($outlet)
            ->where('is_superadmin', false)
            ->with('roles:id,name')
            ->orderBy('name')
            ->get();
    }

    public function isMemberOf(Team $outlet, User $user): bool
    {
        return $this->memberQuery($outlet)->whereKey($user->id)->exists();
    }

    /**
     * Every user with their outlet memberships.
     *
     * @return Collection<int, User>
     */
    public function allWithOutlets(): Collection
    {
        return User::query()
            ->with(['teamMemberships' => fn ($query) => $query->with('team:id,name')])
            ->orderBy('name')
            ->get();
    }

    /**
     * Role names assigned per "userId:outletId" key.
     *
     * @return \Illuminate\Support\Collection<string, \Illuminate\Support\Collection<int, object>>
     */
    public function assignedRolesByOutlet(): \Illuminate\Support\Collection
    {
        return DB::table('model_has_roles')
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->where('model_has_roles.model_type', (new User)->getMorphClass())
            ->orderBy('roles.name')
            ->get(['model_has_roles.model_id as user_id', 'model_has_roles.team_id', 'roles.name'])
            ->groupBy(fn ($row) => $row->user_id.':'.$row->team_id);
    }

    /**
     * @return array{users: int, superadmins: int}
     */
    public function counts(): array
    {
        return [
            'users' => User::count(),
            'superadmins' => User::where('is_superadmin', true)->count(),
        ];
    }

    /**
     * @param  array{name: string, email: string, password: string}  $data
     * @param  array<int, string>  $roles
     */
    public function createForOutlet(Team $outlet, array $data, array $roles): User
    {
        return DB::transaction(function () use ($outlet, $data, $roles) {
            $user = User::create($data);
            $user->forceFill(['email_verified_at' => now()])->save();

            $outlet->memberships()->create(['user_id' => $user->id, 'role' => TeamRole::Member]);
            $user->switchTeam($outlet);

            $user->syncRoles($roles);

            return $user;
        });
    }

    /**
     * @param  array{name: string, email: string}  $data
     * @param  array<int, string>  $roles
     */
    public function update(User $user, array $data, ?string $password, array $roles): User
    {
        $user->fill($data);

        if ($password !== null && $password !== '') {
            $user->password = $password;
        }

        $user->save();
        $user->syncRoles($roles);

        return $user;
    }

    /**
     * Remove the user from the outlet, deleting the account when no other outlet remains.
     */
    public function removeFromOutlet(Team $outlet, User $user): void
    {
        DB::transaction(function () use ($user, $outlet) {
            $user->roles()->detach();
            $outlet->memberships()->where('user_id', $user->id)->delete();

            if ($user->current_team_id === $outlet->id) {
                $user->update(['current_team_id' => $user->fallbackTeam()?->id]);
            }

            if (! $user->teams()->exists()) {
                $user->delete();
            }
        });
    }

    /**
     * @return Builder<User>
     */
    private function memberQuery(Team $outlet): Builder
    {
        return User::query()->whereHas('teams', fn ($query) => $query->where('teams.id', $outlet->id));
    }
}
