<?php

namespace Tests\Feature;

use App\Models\Pengajuan;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_pengajuan_contoh_tidak_diisi_di_luar_lokal_dan_testing(): void
    {
        $this->app['env'] = 'production';

        $this->artisan('db:seed', ['--force' => true])->assertSuccessful();

        $this->assertSame(0, Pengajuan::query()->count());
    }

    public function test_pengajuan_contoh_tetap_diisi_di_lokal(): void
    {
        $this->app['env'] = 'local';

        $this->seed(DatabaseSeeder::class);

        $this->assertGreaterThan(0, Pengajuan::query()->count());
    }
}
