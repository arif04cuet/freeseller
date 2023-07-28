<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        \App\Models\User::factory()->create([
            'name' => 'Arif',
            'email' => 'arif04cuet@gmail.com',
            'mobile' => '01717348147',
            'password' => Hash::make('admin123'),
            'email_verified_at' => now(),
            'is_active' => 1
        ]);

        Artisan::call('shield:install --fresh --minimal');

        Artisan::call('shield:super-admin --user=1');
    }
}
