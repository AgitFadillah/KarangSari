<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     *
     * Data pengajuan contoh hanya diisi di lingkungan lokal dan testing agar
     * dashboard desa di produksi tidak pernah terisi pengajuan karangan.
     */
    public function run(): void
    {
        $this->call([
            ProfilDesaSeeder::class,
            JenisSuratSeeder::class,
            AdminSeeder::class,
            KontenWebsiteSeeder::class,
        ]);

        if (app()->environment('local', 'testing')) {
            $this->call(DemoPengajuanSeeder::class);
        }
    }
}
