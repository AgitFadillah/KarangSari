<?php

namespace Tests\Feature;

use App\Enums\RoleAdmin;
use App\Enums\StatusPengajuan;
use App\Models\JenisSurat;
use App\Models\Pengajuan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Detail pengajuan di panel admin.
 *
 * Keluhan perangkat desa: alur verifikasi masih berupa satu dropdown status
 * di samping, dan nomor WhatsApp hanya bisa disalin. Yang dibutuhkan adalah
 * tampilan seperti mockup admin/detail-pengajuan.html: tombol aksi langsung
 * (Setujui / Minta perbaikan / Tolak) di kepala halaman, kolom pratinjau surat
 * di kanan, serta nomor surat yang bisa langsung diisi dan disimpan.
 */
class DetailPengajuanAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        \App\Support\SiteProfile::lupakan();
    }

    protected function admin(string $username = 'sari', RoleAdmin $role = RoleAdmin::Administrator): User
    {
        return User::firstOrCreate(
            ['username' => $username],
            [
                'name' => ucfirst($username),
                'email' => $username.'@example.test',
                'password' => bcrypt('rahasia'),
                'role' => $role,
                'is_active' => true,
            ]
        );
    }

    protected function pengajuan(StatusPengajuan $status = StatusPengajuan::MenungguVerifikasi): Pengajuan
    {
        $jenis = JenisSurat::query()->firstOrCreate(
            ['slug' => 'domisili'],
            [
                'kode' => 'SKD',
                'nama' => 'Surat Keterangan Domisili',
                'kode_layanan' => '470',
                'estimasi_hari' => '1 hari kerja',
                'deskripsi' => 'Keterangan tempat tinggal.',
                'template_surat' => 'Nomor: {{nomor_surat}}'."\n"
                    .'Yang bertanda tangan di bawah ini menerangkan bahwa {{nama}} berdomisili di {{alamat}}.',
                'is_aktif' => true,
            ]
        );

        $pengajuan = new Pengajuan;
        $pengajuan->forceFill([
            'jenis_surat_id' => $jenis->id,
            'nomor_referensi' => 'KRS-2026-0001',
            'nama' => 'Faiz Ridho',
            'alamat' => 'Karang Sari',
            'keperluan' => 'Perpindahan Domisili',
            'whatsapp' => '08123456789',
            'persetujuan_data' => true,
            'persetujuan_at' => now(),
        ]);
        $pengajuan->isiStatus($status);
        $pengajuan->save();

        return $pengajuan->refresh();
    }

    public function test_tombol_aksi_muncul_untuk_transisi_yang_sah(): void
    {
        $pengajuan = $this->pengajuan(StatusPengajuan::MenungguVerifikasi);

        $this->actingAs($this->admin())
            ->get(route('admin.pengajuan.show', $pengajuan))
            ->assertOk()
            // Menunggu verifikasi boleh ke Disetujui / Perlu Perbaikan / Ditolak.
            ->assertSee('data-modal-open="modalSetujui"', false)
            ->assertSee('data-modal-open="modalPerbaikan"', false)
            ->assertSee('data-modal-open="modalTolak"', false);
    }

    public function test_tombol_setujui_hilang_saat_status_tidak_boleh_disetujui(): void
    {
        // Pengajuan yang sudah disetujui tidak boleh disetujui lagi.
        $pengajuan = $this->pengajuan(StatusPengajuan::Disetujui);

        $this->actingAs($this->admin())
            ->get(route('admin.pengajuan.show', $pengajuan))
            ->assertOk()
            ->assertDontSee('data-modal-open="modalSetujui"', false)
            ->assertDontSee('data-modal-open="modalPerbaikan"', false)
            // Masih boleh ditolak menurut alur.
            ->assertSee('data-modal-open="modalTolak"', false);
    }

    /**
     * Pengajuan berstatus "Perlu Perbaikan" tetap harus bisa DITERIMA admin.
     *
     * Sebelumnya transisinya hanya Menunggu Verifikasi & Ditolak, sehingga
     * panel "Ubah status" tidak pernah menampilkan pilihan terima — petugas
     * tidak punya jalan untuk menyetujui pengajuan yang sudah dibetulkan.
     */
    public function test_pengajuan_perlu_perbaikan_bisa_diterima_admin(): void
    {
        $pengajuan = $this->pengajuan(StatusPengajuan::PerluPerbaikan);
        $pengajuan->catatan_admin = 'Mohon lengkapi isian.';
        $pengajuan->save();

        // Tombol aksi cepat "Terima pengajuan" tampil di kepala halaman.
        $this->actingAs($this->admin())
            ->get(route('admin.pengajuan.show', $pengajuan))
            ->assertOk()
            ->assertSee('data-modal-open="modalSetujui"', false)
            ->assertSee('Terima pengajuan')
            // Pilihan terima juga ada di dropdown "Ubah status".
            ->assertSee('Disetujui — terima pengajuan')
            // Tidak masuk akal meminta perbaikan lagi pada status ini.
            ->assertDontSee('data-modal-open="modalPerbaikan"', false);

        // Dan benar-benar bisa disimpan.
        $this->actingAs($this->admin())
            ->patch(route('admin.pengajuan.status', $pengajuan), [
                'status' => StatusPengajuan::Disetujui->value,
                'catatan' => 'Sudah diperiksa, isian lengkap.',
            ])
            ->assertRedirect(route('admin.pengajuan.show', $pengajuan))
            ->assertSessionHasNoErrors();

        $pengajuan->refresh();
        $this->assertSame(StatusPengajuan::Disetujui, $pengajuan->status);
        $this->assertSame('Sudah diperiksa, isian lengkap.', $pengajuan->catatan_admin);
        $this->assertNotNull($pengajuan->diverifikasi_at);
        $this->assertDatabaseHas('riwayat_pengajuans', [
            'pengajuan_id' => $pengajuan->id,
            'status' => StatusPengajuan::Disetujui->value,
            'aktor' => 'admin',
        ]);
    }

    public function test_pengajuan_selesai_tidak_menampilkan_tombol_aksi(): void
    {
        $pengajuan = $this->pengajuan(StatusPengajuan::Selesai);

        $this->actingAs($this->admin())
            ->get(route('admin.pengajuan.show', $pengajuan))
            ->assertOk()
            ->assertDontSee('data-modal-open="modalSetujui"', false)
            ->assertDontSee('data-modal-open="modalPerbaikan"', false)
            ->assertDontSee('data-modal-open="modalTolak"', false)
            ->assertSee('tidak dapat diubah lagi');
    }

    public function test_setujui_lewat_modal_menyimpan_status_dan_catatan(): void
    {
        $pengajuan = $this->pengajuan(StatusPengajuan::MenungguVerifikasi);

        $this->actingAs($this->admin())
            ->patch(route('admin.pengajuan.status', $pengajuan), [
                'status' => StatusPengajuan::Disetujui->value,
                'catatan' => 'Berkas lengkap, surat diproses 1 hari kerja.',
            ])
            ->assertRedirect(route('admin.pengajuan.show', $pengajuan))
            ->assertSessionHasNoErrors();

        $pengajuan->refresh();
        $this->assertSame(StatusPengajuan::Disetujui, $pengajuan->status);
        $this->assertSame('Berkas lengkap, surat diproses 1 hari kerja.', $pengajuan->catatan_admin);
    }

    public function test_tolak_tanpa_alasan_ditolak_oleh_validasi(): void
    {
        $pengajuan = $this->pengajuan(StatusPengajuan::MenungguVerifikasi);

        $this->actingAs($this->admin())
            ->patch(route('admin.pengajuan.status', $pengajuan), [
                'status' => StatusPengajuan::Ditolak->value,
                'catatan' => '',
            ])
            ->assertSessionHasErrors('catatan');

        $this->assertSame(StatusPengajuan::MenungguVerifikasi, $pengajuan->refresh()->status);
    }

    public function test_nomor_surat_dapat_disimpan_tanpa_mengubah_status(): void
    {
        $pengajuan = $this->pengajuan(StatusPengajuan::MenungguVerifikasi);

        $this->actingAs($this->admin())
            ->put(route('admin.pengajuan.nomor-surat', $pengajuan), [
                'nomor_surat' => '470/12/DS-KRS/2026',
            ])
            ->assertRedirect(route('admin.pengajuan.show', $pengajuan))
            ->assertSessionHasNoErrors();

        $pengajuan->refresh();
        $this->assertSame('470/12/DS-KRS/2026', $pengajuan->nomor_surat);
        // Nomor surat tidak menggeser alur status.
        $this->assertSame(StatusPengajuan::MenungguVerifikasi, $pengajuan->status);
    }

    public function test_nomor_surat_tercetak_pada_pratinjau(): void
    {
        $pengajuan = $this->pengajuan(StatusPengajuan::MenungguVerifikasi);
        $pengajuan->nomor_surat = '470/99/DS-KRS/2026';
        $pengajuan->save();

        // Pratinjau memakai surat resmi desa, yang menulis "Nomor : " dengan
        // spasi sebelum titik dua seperti dokumen Word desa.
        $this->actingAs($this->admin())
            ->get(route('admin.pengajuan.show', $pengajuan))
            ->assertOk()
            ->assertSee('Nomor : 470/99/DS-KRS/2026');
    }

    public function test_pratinjau_surat_dan_nomor_surat_tampil(): void
    {
        $pengajuan = $this->pengajuan(StatusPengajuan::MenungguVerifikasi);

        $this->actingAs($this->admin())
            ->get(route('admin.pengajuan.show', $pengajuan))
            ->assertOk()
            ->assertSee('Preview surat')
            ->assertSee('Nomor surat')
            ->assertSee('Simpan nomor')
            ->assertSee('Faiz Ridho');
    }

    /**
     * Tombol WhatsApp harus langsung membuka percakapan ke warga, bukan
     * menyalin nomor. Nomor tersimpan "08..." diubah ke format wa.me "62...".
     */
    public function test_tombol_whatsapp_membuka_chat_ke_warga_bukan_menyalin_nomor(): void
    {
        $pengajuan = $this->pengajuan(StatusPengajuan::MenungguVerifikasi);

        $this->actingAs($this->admin())
            ->get(route('admin.pengajuan.show', $pengajuan))
            ->assertOk()
            ->assertSee('Chat WhatsApp warga')
            ->assertSee('https://wa.me/628123456789', false)
            // Pesan awal memuat nomor referensi agar warga tahu konteksnya.
            ->assertSee(rawurlencode($pengajuan->nomor_referensi), false)
            // Tidak ada lagi tombol yang hanya menyalin nomor.
            ->assertDontSee('Salin nomor WhatsApp')
            ->assertDontSee('data-copy="#waPemohon"', false);
    }

    public function test_tautan_whatsapp_mengubah_nomor_lokal_ke_format_internasional(): void
    {
        $pengajuan = $this->pengajuan(StatusPengajuan::MenungguVerifikasi);

        $this->assertSame('628123456789', $pengajuan->whatsappInternasional());
        $this->assertStringStartsWith('https://wa.me/628123456789?text=', $pengajuan->tautanWhatsapp());

        // Nomor yang sudah dalam format 62 tidak digandakan.
        $pengajuan->whatsapp = '628123456789';
        $this->assertSame('628123456789', $pengajuan->whatsappInternasional());

        // Tanpa nomor, tidak ada tombol yang bisa diklik.
        $pengajuan->whatsapp = '';
        $this->assertNull($pengajuan->tautanWhatsapp());
    }

    public function test_operator_konten_tidak_boleh_membuka_detail_pengajuan(): void
    {
        $pengajuan = $this->pengajuan(StatusPengajuan::MenungguVerifikasi);

        // Operator konten tidak berwenang atas data warga sama sekali:
        // bukan hanya tombol aksinya yang hilang, halamannya pun ditolak.
        $this->actingAs($this->admin('dewi', RoleAdmin::OperatorKonten))
            ->get(route('admin.pengajuan.show', $pengajuan))
            ->assertForbidden();

        $this->actingAs($this->admin('dewi', RoleAdmin::OperatorKonten))
            ->get(route('admin.pengajuan.surat-word', $pengajuan))
            ->assertForbidden();
    }

    public function test_detail_menampilkan_kode_akses_dan_tautan_wa_memuatnya(): void
    {
        $pengajuan = $this->pengajuan();
        $pengajuan->forceFill(['kode_akses' => 'KODEAB12'])->save();

        $this->actingAs($this->admin())
            ->get(route('admin.pengajuan.show', $pengajuan))
            ->assertOk()
            ->assertSee('Kode akses warga')
            ->assertSee('KODEAB12');

        $this->assertStringContainsString(
            rawurlencode('Kode akses: KODEAB12'),
            (string) $pengajuan->fresh()->tautanWhatsapp()
        );
    }

    public function test_operator_konten_tidak_dapat_menyimpan_nomor_surat(): void
    {
        $pengajuan = $this->pengajuan(StatusPengajuan::MenungguVerifikasi);

        $this->actingAs($this->admin('dewi', RoleAdmin::OperatorKonten))
            ->put(route('admin.pengajuan.nomor-surat', $pengajuan), [
                'nomor_surat' => '470/12/DS-KRS/2026',
            ])
            ->assertForbidden();

        $this->assertNull($pengajuan->refresh()->nomor_surat);
    }
}
