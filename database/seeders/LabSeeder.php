<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Lab;

class LabSeeder extends Seeder
{
    public function run()
    {
        $labs = [
            'Lab Game & Multimedia',
            'Lab Sistem Komputer & Komunikasi Data',
            'Lab Rekayasa Perangkat Lunak',
            'Lab Algoritma & Pemrograman',
            'Lab Komputasi',
            'Lab Sistem Cerdas',
            'Lab Ruang Riset'
        ];

        foreach ($labs as $lab) {
            Lab::firstOrCreate(['name' => $lab], [
                'description' => 'Inventaris ' . $lab
            ]);
        }
    }
}
