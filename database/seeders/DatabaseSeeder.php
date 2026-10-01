<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Sector;
use App\Models\SectorPermission;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $sector = Sector::firstOrCreate(['sector' => 'Demo'], ['enabled' => true]);

        // Synthetic development account; password is the UserFactory default.
        User::firstOrCreate(['email' => 'test@example.com'], User::factory()->make([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'sector_id' => $sector->id,
            'enabled' => true,
            'level' => 2,
        ])->getAttributes());

        // Only the minimal read-only menu needed to verify navigation.
        $permission = Permission::firstOrCreate(['route' => 'users'], [
            'menu' => 'administration',
            'menu_label' => 'Administration',
            'route_label' => 'Users',
            'icon_menu' => 'bi bi-gear',
            'icon_route' => 'bi bi-person',
        ]);

        SectorPermission::firstOrCreate([
            'sector_id' => $sector->id,
            'permission_id' => $permission->id,
            'read_write' => 'R',
        ], ['level' => 1, 'enabled' => true]);
    }
}
