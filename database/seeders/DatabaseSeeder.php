<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{

    public function run(): void
    {

        // Ejecutar seeders de la API SUNAT
        $this->call([
            /* RolesAndPermissionsSeeder::class, */
            UbiRegionesSeeder::class,
            UbiProvinciasSeeder::class,
            UbiDistritoSeeder::class
        ]);
    }
}
