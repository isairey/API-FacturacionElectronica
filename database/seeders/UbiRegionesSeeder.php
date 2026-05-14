<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UbiRegionesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
       $regiones = [
            ['id' => '010000', 'nombre' => 'Amazonas'],
            ['id' => '020000', 'nombre' => 'Áncash'],
            ['id' => '030000', 'nombre' => 'Apurímac'],
            ['id' => '040000', 'nombre' => 'Arequipa'],
            ['id' => '050000', 'nombre' => 'Ayacucho'],
            ['id' => '060000', 'nombre' => 'Cajamarca'],
            ['id' => '070000', 'nombre' => 'Callao'],
            ['id' => '080000', 'nombre' => 'Cusco'],
            ['id' => '090000', 'nombre' => 'Huancavelica'],
            ['id' => '100000', 'nombre' => 'Huánuco'],
            ['id' => '110000', 'nombre' => 'Ica'],
            ['id' => '120000', 'nombre' => 'Junín'],
            ['id' => '130000', 'nombre' => 'La Libertad'],
            ['id' => '140000', 'nombre' => 'Lambayeque'],
            ['id' => '150000', 'nombre' => 'Lima'],
            ['id' => '160000', 'nombre' => 'Loreto'],
            ['id' => '170000', 'nombre' => 'Madre de Dios'],
            ['id' => '180000', 'nombre' => 'Moquegua'],
            ['id' => '190000', 'nombre' => 'Pasco'],
            ['id' => '200000', 'nombre' => 'Piura'],
            ['id' => '210000', 'nombre' => 'Puno'],
            ['id' => '220000', 'nombre' => 'San Martín'],
            ['id' => '230000', 'nombre' => 'Tacna'],
            ['id' => '240000', 'nombre' => 'Tumbes'],
            ['id' => '250000', 'nombre' => 'Ucayali'],
        ];
        DB::table('ubi_regiones')->insert($regiones);
    }
}
