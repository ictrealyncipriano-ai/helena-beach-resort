<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@helenaresort.com'],
            [
                'name' => 'Super Admin',
                'password' => 'password',
                'role' => 'super_admin',
            ]
        );

        User::updateOrCreate(
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
        ]);
    }
}
