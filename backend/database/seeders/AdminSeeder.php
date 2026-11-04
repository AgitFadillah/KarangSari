<?php

namespace Database\Seeders;

use App\Enums\RoleAdmin;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Tiga akun admin awal. Kata sandi TIDAK ditulis di kode:
 * ambil dari ADMIN_SEED_PASSWORD, atau dibuat acak dan ditampilkan sekali.
 */
class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $sandi = (string) env('ADMIN_SEED_PASSWORD', '');
        $dibuatAcak = false;

        if ($sandi === '') {
            $sandi = Str::password(16, symbols: false);
            $dibuatAcak = true;
        }

        if (strlen($sandi) < 12) {
            throw new RuntimeException(
                'ADMIN_SEED_PASSWORD minimal 12 karakter (saat ini '.strlen($sandi).').'
            );
        }

        $akun = [
            ['name' => 'Sari Wulandari', 'username' => 'sari', 'role' => RoleAdmin::Administrator],
            ['name' => 'Rangga Pratama', 'username' => 'rangga', 'role' => RoleAdmin::Verifikator],
            ['name' => 'Dewi Lestari', 'username' => 'dewi', 'role' => RoleAdmin::OperatorKonten],
        ];

        foreach ($akun as $data) {
            User::query()->updateOrCreate(
                ['username' => $data['username']],
                [
                    'name' => $data['name'],
                    'email' => $data['username'].'@sukamaju.desa.id',
                    'password' => Hash::make($sandi),
                    'role' => $data['role'],
                    'is_active' => true,
                ]
            );
        }

        $this->command?->newLine();
        $this->command?->info('Akun admin awal: '.implode(', ', array_column($akun, 'username')));

        if ($dibuatAcak) {
            $this->command?->warn('ADMIN_SEED_PASSWORD kosong — kata sandi acak dibuat: '.$sandi);
            $this->command?->warn('Simpan kata sandi ini sekarang; tidak ditampilkan lagi setelah seeder berakhir.');
        } else {
            $this->command?->info('Kata sandi diambil dari ADMIN_SEED_PASSWORD.');
        }
        $this->command?->newLine();
    }
}
