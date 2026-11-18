<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\JenisSurat;
use App\Models\LogAktivitas;
use App\Services\TemplateSuratService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Kelola layanan surat: daftar, tambah, konfigurasi, dan aktif/nonaktif.
 *
 * Konfigurasi di sini menentukan apa yang dilihat warga pada katalog layanan
 * dan formulir pengajuan:
 *
 *  - field formulir  -> kolom isian tambahan (dari template {{...}} + konfigurasi)
 *  - persyaratan     -> dokumen yang wajib/opsional diunggah, beserta format & ukuran
 *  - template surat  -> kerangka surat jadi yang dilihat warga saat pratinjau
 */
class JenisSuratController extends Controller
{
    /** Tipe input yang didukung untuk field formulir. */
    protected const TIPE_FIELD = ['text', 'textarea', 'number', 'date'];

    public function __construct(protected TemplateSuratService $template) {}

    public function index(Request $request): View
    {
        abort_unless($request->user()->role->bolehKelolaJenisSurat(), 403);

        $cari = trim((string) $request->query('q', ''));
        $hanyaAktif = $request->boolean('aktif');

        $kueri = JenisSurat::query()->terurut()->withCount('persyaratan');

        $kata = \App\Support\Pencarian::siapkan($cari);

        if ($kata !== null) {
            $kueri->where(function ($q) use ($kata) {
                \App\Support\Pencarian::cocok($q, ['nama', 'kode', 'kode_layanan', 'deskripsi'], $kata);
            });
        }

        if ($hanyaAktif) {
            $kueri->aktif();
        }

        $jenisSurats = $kueri->get();

        // Field formulir dihitung dari template + konfigurasi eksplisit.
        foreach ($jenisSurats as $jenis) {
            $jenis->setAttribute('jumlah_field', count($this->template->penandaKustom($jenis)));
        }

        $semua = JenisSurat::query()->withCount('persyaratan')->get();
        $totalField = $semua->sum(fn (JenisSurat $j) => count($this->template->penandaKustom($j)));

        return view('admin.jenis-surat.index', [
            'activeAdmin' => 'admin.jenis-surat.index',
            'jenisSurats' => $jenisSurats,
            'cari' => $cari,
            'hanyaAktif' => $hanyaAktif,
            'ringkasan' => [
                'total' => $semua->count(),
                'aktif' => $semua->where('is_aktif', true)->count(),
                'field' => $totalField,
                'persyaratan' => $semua->sum('persyaratan_count'),
            ],
        ]);
    }

    public function create(Request $request): View
    {
        abort_unless($request->user()->role->bolehKelolaJenisSurat(), 403);

        return view('admin.jenis-surat.form', [
            'activeAdmin' => 'admin.jenis-surat.index',
            'jenisSurat' => new JenisSurat([
                'is_aktif' => true,
                'estimasi_hari' => '2 hari kerja',
                'template_surat' => $this->template->templateBaku(new JenisSurat([
                    'nama' => 'Surat Keterangan',
                ])),
            ]),
            'tipeField' => self::TIPE_FIELD,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->role->bolehKelolaJenisSurat(), 403);

        $data = $this->validasi($request);

        $jenisSurat = JenisSurat::create([
            'slug' => $this->slugUnik($data['nama'], $data['kode']),
            'kode' => strtoupper($data['kode']),
            'kode_layanan' => $data['kode_layanan'] ?? null,
            'nama' => $data['nama'],
            'deskripsi' => $data['deskripsi'] ?? null,
            'estimasi_hari' => $data['estimasi_hari'] ?? null,
            'template_surat' => $data['template_surat'] ?? null,
            'field_formulir' => $this->fieldTersaring($request),
            'is_aktif' => $request->boolean('is_aktif'),
            'urutan' => $data['urutan'] ?? (int) (JenisSurat::query()->max('urutan') + 1),
        ]);

        $this->simpanPersyaratan($request, $jenisSurat);

        LogAktivitas::catat(
            tindakan: 'Menambahkan jenis surat',
            subjek: $jenisSurat->nama,
            tingkat: LogAktivitas::SUKSES,
        );

        return redirect()
            ->route('admin.jenis-surat.edit', $jenisSurat)
            ->with('status', 'Jenis surat "'.$jenisSurat->nama.'" berhasil dibuat.');
    }

    public function edit(JenisSurat $jenisSurat): View
    {
        abort_unless(request()->user()->role->bolehKelolaJenisSurat(), 403);

        $jenisSurat->load('persyaratan');

        return view('admin.jenis-surat.form', [
            'activeAdmin' => 'admin.jenis-surat.index',
            'jenisSurat' => $jenisSurat,
            'tipeField' => self::TIPE_FIELD,
        ]);
    }

    public function update(Request $request, JenisSurat $jenisSurat): RedirectResponse
    {
        abort_unless($request->user()->role->bolehKelolaJenisSurat(), 403);

        $data = $this->validasi($request, $jenisSurat);

        $jenisSurat->update([
            'kode' => strtoupper($data['kode']),
            'kode_layanan' => $data['kode_layanan'] ?? null,
            'nama' => $data['nama'],
            'deskripsi' => $data['deskripsi'] ?? null,
            'estimasi_hari' => $data['estimasi_hari'] ?? null,
            'template_surat' => $data['template_surat'] ?? null,
            'field_formulir' => $this->fieldTersaring($request),
            'is_aktif' => $request->boolean('is_aktif'),
        ]);

        $this->simpanPersyaratan($request, $jenisSurat);

        LogAktivitas::catat(
            tindakan: 'Memperbarui konfigurasi jenis surat',
            subjek: $jenisSurat->nama,
            tingkat: LogAktivitas::SUKSES,
        );

        return redirect()
            ->route('admin.jenis-surat.edit', $jenisSurat)
            ->with('status', 'Konfigurasi jenis surat tersimpan.');
    }

    /** Aktif/nonaktif cepat dari daftar (toggle pada tabel). */
    public function toggleAktif(Request $request, JenisSurat $jenisSurat): RedirectResponse
    {
        abort_unless($request->user()->role->bolehKelolaJenisSurat(), 403);

        $jenisSurat->update(['is_aktif' => ! $jenisSurat->is_aktif]);

        LogAktivitas::catat(
            tindakan: $jenisSurat->is_aktif
                ? 'Mengaktifkan jenis surat'
                : 'Menonaktifkan jenis surat',
            subjek: $jenisSurat->nama,
            tingkat: LogAktivitas::SUKSES,
        );

        return back()->with('status', $jenisSurat->is_aktif
            ? 'Jenis surat "'.$jenisSurat->nama.'" diaktifkan.'
            : 'Jenis surat "'.$jenisSurat->nama.'" dinonaktifkan. Pengajuan lama tetap dapat dibaca.');
    }

    /**
     * Hapus jenis surat. Ditolak bila sudah punya pengajuan agar riwayat warga
     * tidak ikut hilang — admin cukup menonaktifkannya.
     */
    public function destroy(Request $request, JenisSurat $jenisSurat): RedirectResponse
    {
        abort_unless($request->user()->role->bolehKelolaJenisSurat(), 403);

        if ($jenisSurat->pengajuan()->exists()) {
            return back()->withErrors([
                'jenis_surat' => 'Jenis surat ini sudah dipakai pada pengajuan warga, jadi tidak boleh dihapus. Nonaktifkan saja agar tidak muncul lagi di katalog.',
            ]);
        }

        $nama = $jenisSurat->nama;
        $jenisSurat->delete();

        LogAktivitas::catat(
            tindakan: 'Menghapus jenis surat',
            subjek: $nama,
            tingkat: LogAktivitas::SUKSES,
        );

        return redirect()
            ->route('admin.jenis-surat.index')
            ->with('status', 'Jenis surat "'.$nama.'" dihapus.');
    }

    /**
     * @return array<string, mixed>
     */
    protected function validasi(Request $request, ?JenisSurat $jenisSurat = null): array
    {
        // Rule::unique(...)->ignore(null) aman untuk data baru. Sebelumnya dipakai
        // 'unique:jenis_surats,kode,'.$id yang menghasilkan "id <> ''" saat $id
        // null; PostgreSQL menolak string kosong untuk kolom bigint sehingga
        // SEMUA penyimpanan jenis surat gagal 500 di produksi (SQLite lolos).
        $unik = Rule::unique('jenis_surats', 'kode')->ignore($jenisSurat?->id);

        return $request->validate([
            'kode' => ['required', 'string', 'max:20', $unik],
            'kode_layanan' => ['nullable', 'string', 'max:20'],
            'nama' => ['required', 'string', 'max:255'],
            'deskripsi' => ['nullable', 'string', 'max:2000'],
            'estimasi_hari' => ['nullable', 'string', 'max:30'],
            'template_surat' => ['nullable', 'string', 'max:20000'],
            'urutan' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_aktif' => ['nullable', 'boolean'],

            'field' => ['nullable', 'array'],
            'field.*.label' => ['nullable', 'string', 'max:120'],
            'field.*.kunci' => ['nullable', 'string', 'max:60', 'regex:/^[a-z0-9_]*$/'],
            'field.*.tipe' => ['nullable', 'in:'.implode(',', self::TIPE_FIELD)],
            'field.*.wajib' => ['nullable', 'boolean'],

            'persyaratan' => ['nullable', 'array'],
            'persyaratan.*.id' => ['nullable', 'integer'],
            'persyaratan.*.label' => ['nullable', 'string', 'max:160'],
            'persyaratan.*.format' => ['nullable', 'string', 'max:100'],
            'persyaratan.*.maks_kb' => ['nullable', 'integer', 'min:64', 'max:10240'],
            'persyaratan.*.wajib' => ['nullable', 'boolean'],
        ], [
            'kode.required' => 'Kode jenis surat wajib diisi.',
            'kode.unique' => 'Kode jenis surat sudah dipakai jenis lain.',
            'nama.required' => 'Nama jenis surat wajib diisi.',
            'field.*.kunci.regex' => 'Kunci field hanya boleh huruf kecil, angka, dan garis bawah.',
            'persyaratan.*.maks_kb.min' => 'Ukuran minimal 64 KB.',
            'persyaratan.*.maks_kb.max' => 'Ukuran maksimal 10.240 KB (10 MB).',
        ]);
    }

    /**
     * Field formulir dari input dinamis, dibersihkan dari baris kosong
     * dan kunci ganda.
     *
     * @return array<int, array{kunci: string, label: string, tipe: string, wajib: bool}>
     */
    protected function fieldTersaring(Request $request): array
    {
        $baris = $request->input('field', []);

        if (! is_array($baris)) {
            return [];
        }

        $hasil = [];
        $dilihat = [];

        foreach ($baris as $item) {
            if (! is_array($item)) {
                continue;
            }

            $label = trim((string) ($item['label'] ?? ''));
            $kunci = strtolower(trim((string) ($item['kunci'] ?? '')));

            // Bila kunci kosong, turunkan dari label agar admin tidak perlu dua kali kerja.
            if ($kunci === '' && $label !== '') {
                $kunci = Str::slug($label, '_');
            }

            if ($kunci === '' || isset($dilihat[$kunci])) {
                continue;
            }

            $dilihat[$kunci] = true;
            $hasil[] = [
                'kunci' => $kunci,
                'label' => $label !== '' ? $label : $kunci,
                'tipe' => in_array($item['tipe'] ?? '', self::TIPE_FIELD, true) ? $item['tipe'] : 'text',
                'wajib' => (bool) ($item['wajib'] ?? false),
            ];
        }

        return $hasil;
    }

    /**
     * Simpan ulang seluruh persyaratan dokumen (urut sesuai tampilan).
     *
     * Penting: baris yang sudah ada diperbarui DI TEMPAT, bukan dihapus lalu
     * dibuat ulang. ID persyaratan dirujuk oleh dokumen_pengajuans milik warga
     * (kolom persyaratan_surat_id, nullOnDelete). Versi lama menghapus semua
     * baris terlebih dahulu, sehingga setiap kali admin menyimpan form jenis
     * surat — bahkan hanya untuk mengubah deskripsi — seluruh dokumen warga
     * kehilangan tautannya dan menjadi yatim: petugas tidak lagi bisa melihat
     * dokumen itu milik persyaratan mana.
     */
    protected function simpanPersyaratan(Request $request, JenisSurat $jenisSurat): void
    {
        $baris = $request->input('persyaratan', []);

        if (! is_array($baris)) {
            return;
        }

        $tersimpan = $jenisSurat->persyaratan()->get()->keyBy('id');
        $dipakai = [];
        $urutan = 0;

        foreach ($baris as $item) {
            if (! is_array($item)) {
                continue;
            }

            $label = trim((string) ($item['label'] ?? ''));

            if ($label === '') {
                continue;
            }

            $urutan++;

            $atribut = [
                'label' => $label,
                'format' => $this->formatBersih($item['format'] ?? null),
                'maks_kb' => filled($item['maks_kb'] ?? null) ? (int) $item['maks_kb'] : null,
                'wajib' => (bool) ($item['wajib'] ?? false),
                'urutan' => $urutan,
            ];

            $id = filter_var($item['id'] ?? null, FILTER_VALIDATE_INT);

            // Perbarui baris lama bila ID-nya memang milik jenis surat ini.
            if ($id !== false && $id !== null && $tersimpan->has($id)) {
                $tersimpan->get($id)->update($atribut);
                $dipakai[] = $id;

                continue;
            }

            $jenisSurat->persyaratan()->create($atribut);
        }

        // Hapus hanya baris yang benar-benar dibuang admin dari form.
        $dibuang = $tersimpan->keys()->diff($dipakai);

        if ($dibuang->isNotEmpty()) {
            $jenisSurat->persyaratan()->whereIn('id', $dibuang->all())->delete();
        }
    }

    /** "JPG, PNG , pdf" -> "jpg,png,pdf" agar mudah divalidasi. */
    protected function formatBersih(mixed $nilai): ?string
    {
        $bagian = preg_split('/[\s,|]+/', strtolower((string) $nilai), -1, PREG_SPLIT_NO_EMPTY);

        $bagian = array_values(array_filter($bagian, fn (string $f) => preg_match('/^[a-z0-9]+$/', $f) === 1));

        return $bagian === [] ? null : implode(',', array_unique($bagian));
    }

    /** Slug unik untuk URL publik; dipakai getRouteKeyName(). */
    protected function slugUnik(string $nama, string $kode): string
    {
        $dasar = Str::slug($nama) ?: Str::slug($kode);
        $slug = $dasar;
        $i = 2;

        while (JenisSurat::query()->where('slug', $slug)->exists()) {
            $slug = $dasar.'-'.$i;
            $i++;
        }

        return $slug;
    }
}
