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
        User::firstOrCreate(
            ['email' => 'demo@shopmate.test'],
            ['name' => 'Demo User', 'password' => bcrypt('password')],
        );

        User::firstOrCreate(
            ['email' => 'admin@shopmate.test'],
            ['name' => 'Admin User', 'password' => bcrypt('password'), 'is_admin' => true],
        );

        $this->call(ProductCatalogSeeder::class);
    }
}
