<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Main seeder: creates default admin/staff users and populates
 * initial data (settings, cottages, FAQs, etc.).
 * Uses firstOrCreate to avoid duplicates on re-seed.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $adminPassword = $this->initialPassword(
            'ADMIN_INITIAL_PASSWORD',
            'admin@helenaresort.com',
            'Super Admin'
        );

        User::firstOrCreate(
            ['email' => 'admin@helenaresort.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make($adminPassword),
                'role' => 'super_admin',
            ]
        );

        $staffPassword = $this->initialPassword(
            'STAFF_INITIAL_PASSWORD',
            'staff@helenaresort.com',
            'Staff'
        );

        User::firstOrCreate(
            ['email' => 'staff@helenaresort.com'],
            [
                'name' => 'Staff',
                'password' => Hash::make($staffPassword),
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

    /**
     * Read an initial password from the environment, or generate a strong
     * random one so the seeder never falls back to a guessable default.
     * When a password is generated it is printed to the console so the
     * operator can capture it before first login.
     */
    private function initialPassword(string $envKey, string $email, string $name): string
    {
        $password = (string) env($envKey, '');

        if ($password === '') {
            $password = Str::random(32);

            $this->command?->warn(sprintf(
                '[DatabaseSeeder] %s is not set. Generated random password for %s (%s): %s',
                $envKey,
                $name,
                $email,
                $password
            ));
        }

        return $password;
    }
}
