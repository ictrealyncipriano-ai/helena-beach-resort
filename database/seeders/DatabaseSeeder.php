<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Main seeder: creates default admin/staff users and populates
 * initial data (settings, cottages, FAQs, etc.).
 * Uses firstOrCreate to avoid duplicates on re-seed.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'admin@helenaresort.com'],
            [
                'name' => 'Super Admin',
                'password' => 'password',
                'role' => 'super_admin',
            ]
        );

        User::firstOrCreate(
            ['email' => 'staff@helenaresort.com'],
            [
                'name' => 'Staff',
                'password' => 'password',
                'role' => 'staff',
            ]
        );

        $this->call([
            SiteSettingSeeder::class,
            CottageSeeder::class,
            PhotoSeeder::class,
            FaqSeeder::class,
            TestimonialSeeder::class,
            ServiceSeeder::class,
        ]);
    }
}
