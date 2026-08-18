<?php

namespace Database\Seeders;

use App\Models\Admin;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $email    = env('ADMIN_SEED_EMAIL');
        $password = env('ADMIN_SEED_PASSWORD');
        $name     = env('ADMIN_SEED_NAME', 'Pixilink Admin');

        if (empty($email) || empty($password)) {
            throw new \RuntimeException(
                'AdminSeeder requires ADMIN_SEED_EMAIL and ADMIN_SEED_PASSWORD env vars. ' .
                'Run: ADMIN_SEED_EMAIL=you@example.com ADMIN_SEED_PASSWORD=yourpassword php artisan db:seed --class=AdminSeeder'
            );
        }

        Admin::updateOrCreate(
            ['email' => $email],
            [
                'name'     => $name,
                'password' => Hash::make($password),
            ]
        );
    }
}
