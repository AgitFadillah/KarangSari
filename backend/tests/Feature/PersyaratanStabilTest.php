<?php

namespace Tests\Feature;

use App\Enums\RoleAdmin;
use App\Enums\StatusPengajuan;
use App\Models\DokumenPengajuan;
use App\Models\JenisSurat;
use App\Models\Pengajuan;
use App\Models\PersyaratanSurat;
use App\Models\User;
use Database\Seeders\JenisSuratSeeder;
use Database\Seeders\ProfilDesaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PersyaratanStabilTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([ProfilDesaSeeder::class, JenisSuratSeeder::class]);
        Storage::fake('local');
        Storage::fake('public');

        $this->admin = User::create([
            'name' => 'Admin Uji',
            'username' => 'adminuji',
            'password' => Hash::make('sandi-uji-test-1234'),
            'role' => RoleAdmin::Administrator,
            'is_active' => true,
        ]);
    }

    public function test_simpan_ulang_persyaratan_tidak_melepas_dokumen_warga(): void
    {
        $jenis = JenisSurat::query()->where('slug', 'domisili')->firstOrFail();
        $syarat = PersyaratanSurat::query()->where('jenis_surat_id', $jenis->id)->firstOrFail();
        $syarat->update(['wajib' => true]);

        $p = new Pengajuan;
        $p->fill([
            'jenis_surat_id' => $jenis->id,
            'nama' => 'Budi',
            'nik' => '3201121508870003',
            'tempat_lahir' => 'Bogor',
            'tanggal_lahir' => '1987-08-15',
            'alamat' => 'Kp. Pasir',
            'keperluan' => 'Administrasi',
            'whatsapp' => '081234567890',
            'persetujuan_data' => true,
            'persetujuan_at' => now(),
        ]);
        $p->nomor_referensi = 'KRS-2026-0001';
        $p->isiStatus(StatusPengajuan::MenungguVerifikasi);
        $p->save();

        $dok = $p->dokumen()->create([
            'persyaratan_surat_id' => $syarat->id,
            'label' => $syarat->label,
            'path' => 'dokumen/2026/x/ktp.pdf',
            'disk' => 'local',
            'nama_asli' => 'ktp.pdf',
            'mime' => 'application/pdf',
            'ukuran' => 100,
        ]);

        // Admin menyimpan ulang form jenis surat (mis. hanya mengubah deskripsi).
        $this->actingAs($this->admin)
            ->put(route('admin.jenis-surat.update', $jenis), [
                'kode' => $jenis->kode,
                'nama' => $jenis->nama,
                'deskripsi' => 'Deskripsi baru dari admin.',
                'is_aktif' => '1',
                'persyaratan' => [
                    ['id' => $syarat->id, 'label' => $syarat->label, 'format' => 'pdf,jpg', 'maks_kb' => 2048, 'wajib' => '1'],
                ],
            ])->assertRedirect();

        $dok->refresh();

        $this->assertNotNull(
            $dok->persyaratan_surat_id,
            'Dokumen warga kehilangan tautan ke persyaratannya setelah admin menyimpan form.'
        );

        $syaratBaru = PersyaratanSurat::query()->where('jenis_surat_id', $jenis->id)->firstOrFail();
        $this->assertSame(
            $syaratBaru->id,
            $dok->persyaratan_surat_id,
            'Dokumen warga harus ikut pindah ke baris persyaratan yang baru.'
        );
    }

    public function test_form_admin_mengirim_id_persyaratan_yang_sudah_ada(): void
    {
        $jenis = JenisSurat::query()->where('slug', 'domisili')->firstOrFail();
        $syarat = PersyaratanSurat::query()->where('jenis_surat_id', $jenis->id)->firstOrFail();

        // Form harus memuat ID tersembunyi; tanpa itu, penyimpanan tidak bisa
        // memperbarui baris lama dan terpaksa menghapus-lalu-membuat-ulang.
        $this->actingAs($this->admin)
            ->get(route('admin.jenis-surat.edit', $jenis))
            ->assertOk()
            ->assertSee('name="persyaratan[0][id]" value="'.$syarat->id.'"', false);
    }

    public function test_persyaratan_yang_dibuang_admin_tetap_terhapus(): void
    {
        $jenis = JenisSurat::query()->where('slug', 'domisili')->firstOrFail();
        $syarat = PersyaratanSurat::query()->where('jenis_surat_id', $jenis->id)->firstOrFail();

        // Dua baris: satu dipertahankan, satu dibuang.
        $kedua = $jenis->persyaratan()->create([
            'label' => 'Fotokopi KK',
            'format' => 'pdf',
            'urutan' => 99,
            'wajib' => false,
        ]);

        $this->actingAs($this->admin)
            ->put(route('admin.jenis-surat.update', $jenis), [
                'kode' => $jenis->kode,
                'nama' => $jenis->nama,
                'is_aktif' => '1',
                'persyaratan' => [
                    ['id' => $syarat->id, 'label' => 'Surat pengantar RT/RW (baru)', 'format' => 'pdf', 'maks_kb' => 1024, 'wajib' => '1'],
                ],
            ])->assertRedirect();

        // Baris yang dipertahankan: ID sama, isi diperbarui, urutan dirapikan.
        $syarat->refresh();
        $this->assertSame('Surat pengantar RT/RW (baru)', $syarat->label);
        $this->assertSame(1024, $syarat->maks_kb);
        $this->assertSame(1, $syarat->urutan);

        // Baris yang dibuang benar-benar hilang.
        $this->assertDatabaseMissing('persyaratan_surats', ['id' => $kedua->id]);
    }

    public function test_menambah_persyaratan_baru_lewat_form(): void
    {
        $jenis = JenisSurat::query()->where('slug', 'domisili')->firstOrFail();
        $syarat = PersyaratanSurat::query()->where('jenis_surat_id', $jenis->id)->firstOrFail();

        $this->actingAs($this->admin)
            ->put(route('admin.jenis-surat.update', $jenis), [
                'kode' => $jenis->kode,
                'nama' => $jenis->nama,
                'is_aktif' => '1',
                'persyaratan' => [
                    ['id' => $syarat->id, 'label' => $syarat->label, 'format' => '', 'maks_kb' => '', 'wajib' => ''],
                    ['id' => '', 'label' => 'Fotokopi KK', 'format' => 'pdf,jpg', 'maks_kb' => 2048, 'wajib' => '1'],
                ],
            ])->assertRedirect();

        $this->assertSame(2, $jenis->persyaratan()->count());
        $this->assertDatabaseHas('persyaratan_surats', [
            'jenis_surat_id' => $jenis->id,
            'label' => 'Fotokopi KK',
            'format' => 'pdf,jpg',
            'maks_kb' => 2048,
            'wajib' => true,
            'urutan' => 2,
        ]);
    }
}
