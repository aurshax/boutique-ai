<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Local admin only (for a future admin panel); the 'hashed' cast hashes the password.
        // Never created in production — a known password on a public site would be an open door.
        if (! app()->isProduction()) {
            User::firstOrCreate(
                ['email' => 'admin@boutique.test'],
                ['name' => 'Admin', 'password' => 'password'],
            );
        }

        $this->call(CatalogSeeder::class);
    }
}
