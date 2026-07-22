<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        if (!User::where('email', 'admin@helenaresort.com')->exists()) {
            User::create([
                'name' => 'Admin',
                'email' => 'admin@helenaresort.com',
                'password' => 'password',
            ]);
        }

        $this->call([
            SiteSettingSeeder::class,
            CottageSeeder::class,
            PhotoSeeder::class,
        ]);
    }
}
