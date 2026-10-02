<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Seed the application's default users.
     */
    public function run(): void
    {
        $this->seedUser('Super Admin', 'superadmin@example.com', superadmin: true);
    }

    private function seedUser(string $name, string $email, ?string $role = null, bool $superadmin = false): void
    {
        if (User::where('email', $email)->exists()) {
            return;
        }

        $user = User::create(['name' => $name, 'email' => $email, 'password' => 'password']);
        $user->forceFill(['email_verified_at' => now(), 'is_superadmin' => $superadmin])->save();

        if ($role !== null) {
            $user->assignRole($role);
        }
    }
}
