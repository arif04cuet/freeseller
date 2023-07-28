<?php

namespace Database\Seeders;

use App\Enum\BusinessType;
use App\Enum\SystemRole;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use Spatie\Permission\Models\Role;


class UserRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        foreach (SystemRole::array() as $key => $name) {

            Role::create(['name' => $key]);
        }
    }
}
