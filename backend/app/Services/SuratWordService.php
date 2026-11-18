<?php

namespace App\Services;

use App\Models\JenisSurat;
use App\Models\Pengajuan;
use App\Support\PetaSuratDesa;
use App\Support\SiteProfile;
use ZipArchive;

/**
 * Mencetak surat desa sebagai dokumen Word yang IDENTIK dengan dokumen asli
 * perangkat desa.
 *
 * Pendekatan: dokumen asli (resources/surat/template-domisili) tidak digambar
 * ulang, melainkan DIPAKAI SEBAGAI KERANGKA. Berkas .docx adalah paket ZIP;
 * seluruh bagian XML di dalamnya disalin apa adanya, lalu hanya isi teks pada
 * simpul <w:t> tertentu yang diganti dengan data warga.
 *
 * Akibatnya huruf Bookman Old Style, ukuran 16/22/24/10/18/12 pt, tab
 * bersarang, perataan kiri-kanan, dan tabel tanda tangan 3 kolom tercetak
 * persis sama — karena memang XML aslinya yang dipakai, bukan tiruan.
 *
 * KOP SURAT
 * ---------
 * Seluruh surat memakai satu kop resmi desa: berkas gambar
 * public/images/kop-surat.png (bersumber dari docs/kop surat.png). Gambar itu
 * sudah memuat lambang, tiga baris nama pemerintah, alamat, dan garis ganda,
 * sehingga hasil cetaknya sama dengan kop pada dokumen Word desa.
 *
 * Empat paragraf kop pada kerangka (lambang + tiga baris teks + dua garis
 * penghubung) diganti dengan satu gambar kop tersebut. Dengan begitu kop tidak
 * pernah tercetak dua kali, dan pratinjau di layar memakai gambar yang sama.
 */
class SuratWordService
{
    /** Folder kerangka dokumen Word desa per kategori. */
    private const KERANGKA = [
        'domisili' => 'surat/domisili',
        'usaha' => 'surat/usaha',
        'tidak-mampu' => 'surat/tidak-mampu',
        'kematian' => 'surat/kematian',
        'belum-menikah' => 'surat/belum-menikah',
        'orang-tua-asuh' => 'surat/orang-tua-asuh',
        'pekerjaan' => 'surat/pekerjaan',
    ];

    /** Kerangka cadangan untuk kategori lama yang belum punya dokumen desa. */
    private const KERANGKA_BAKU = 'surat/template-domisili';

    /**
     * Kop resmi desa, relatif terhadap folder public/.
     *
     * Satu berkas ini dipakai untuk SEMUA template surat: disisipkan ke dalam
     * .docx dan ditampilkan pada pratinjau di layar.
     */
    public const KOP_BERKAS = 'images/kop-surat.png';

    /** Nama bagian kop di dalam paket .docx. */
    private const KOP_BAGIAN = 'word/media/kop-surat.png';

    /** Id relasi gambar kop pada document.xml.rels. */
    private const KOP_RELASI = 'rId100';

    /**
     * Lebar kop dalam EMU (1/914400 inci).
     *
     * 7019778 EMU = 7,68 inci, sama dengan panjang garis ganda kop pada
     * dokumen desa, sehingga kop membentang selebar aslinya.
     */
    private const KOP_LEBAR = 7019778;

    /**
     * Paragraf kop pada kerangka dokumen desa. Empat paragraf ini diganti
     * dengan satu gambar kop.
     *
     * @var array{0: int, 1: int}
     */
    private const KOP_PARAGRAF = [1, 4];

    /**
     * Bagian paket .docx yang disalin (nama di dalam ZIP).
     *
     * @var array<int, string>
     */
    private const BAGIAN = [
        '[Content_Types].xml',
        '_rels/.rels',
        'docProps/app.xml',
        'docProps/core.xml',
        'word/_rels/document.xml.rels',
        'word/document.xml',
        'word/fontTable.xml',
        'word/settings.xml',
        'word/styles.xml',
        'word/webSettings.xml',
        'word/theme/theme1.xml',
        'word/media/image1.png',
    ];

    public function __construct(private TemplateSuratService $template) {}

    /**
     * Susun dokumen Word untuk satu pengajuan, kembalikan isi berkas .docx.
     *
     * @param  array<string, string>  $isian  isian tambahan dari warga
     */
    public function isi(JenisSurat $jenisSurat, ?Pengajuan $pengajuan = null, array $isian = []): string
    {
        $document = $this->documentXml($jenisSurat, $pengajuan, $isian);
        $kop = $this->kopPath();
        $folder = $this->folderKerangka($jenisSurat);

        $sementara = tempnam(sys_get_temp_dir(), 'surat');
        @unlink($sementara);
        $sementara .= '.docx';

        $zip = new ZipArchive;
        $zip->open($sementara, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        foreach (self::BAGIAN as $bagian) {
            if ($bagian === 'word/document.xml') {
                $zip->addFromString($bagian, $document);

                continue;
            }

            // Relasi dokumen perlu tambahan entri untuk gambar kop.
            if ($bagian === 'word/_rels/document.xml.rels') {
                $zip->addFromString($bagian, $this->relsDenganKop($folder));

                continue;
            }

            $isi = $this->bacaKerangka($folder, $bagian);

            if ($isi !== null) {
                $zip->addFromString($bagian, $isi);
            }
        }

        // Gambar kop resmi. Bila berkasnya hilang, surat tetap terbit tanpa
        // kop bergambar — lebih baik daripada gagal total saat warga mengunduh.
        if ($kop !== null) {
            $zip->addFromString(self::KOP_BAGIAN, (string) file_get_contents($kop));
        }

        $zip->close();

        $hasil = (string) file_get_contents($sementara);
        @unlink($sementara);

        return $hasil;
    }

    /**
     * Path fisik gambar kop resmi desa.
     * Mengembalikan null bila berkas belum tersedia.
     */
    public function kopPath(): ?string
    {
        $path = public_path(self::KOP_BERKAS);

        return is_file($path) ? $path : null;
    }

    /**
     * URL gambar kop untuk pratinjau di layar.
     *
     * Memakai URL relatif ("/images/…") supaya tetap benar ketika situs dibuka
     * lewat host apa pun (localhost, 127.0.0.1, IP LAN, atau domain produksi).
     */
    public function kopUrl(): string
    {
        return '/'.ltrim(self::KOP_BERKAS, '/');
    }

    /**
     * document.xml.rels dengan tambahan relasi gambar kop.
     *
     * Entri lain dibiarkan apa adanya; hanya satu relasi baru yang
     * disisipkan sebelum penutup </Relationships>.
     */
    private function relsDenganKop(string $folder): string
    {
        $rels = (string) $this->bacaKerangka($folder, 'word/_rels/document.xml.rels');

        // Jangan menambah relasi bila entri kop sudah ada (mis. kerangka yang
        // kelak memang memuat kop).
        if (str_contains($rels, 'Target="media/kop-surat.png"')) {
            return $rels;
        }

        $relasi = '<Relationship Id="'.self::KOP_RELASI.'"'
            .' Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/image"'
            .' Target="media/kop-surat.png"/>';

        return (string) preg_replace('#</Relationships>#', $relasi.'</Relationships>', $rels, 1);
    }

    /**
     * Simpan sebagai berkas .docx sementara dan kembalikan path-nya.
     * Pemanggil bertanggung jawab menghapus berkas ini.
     */
    public function berkas(JenisSurat $jenisSurat, ?Pengajuan $pengajuan = null, array $isian = []): string
    {
        $folder = storage_path('app/temp');

        if (! is_dir($folder)) {
            mkdir($folder, 0775, true);
        }

        $nama = 'surat-'.($pengajuan?->nomor_referensi ?? 'baru').'-'.bin2hex(random_bytes(4)).'.docx';
        $path = $folder.DIRECTORY_SEPARATOR.$nama;

        file_put_contents($path, $this->isi($jenisSurat, $pengajuan, $isian));

        return $path;
    }

    /**
     * Isi surat sebagai teks biasa, dibaca dari document.xml yang sama dengan
     * berkas .docx.
     *
     * Dipakai untuk pratinjau di layar, sehingga yang dilihat petugas dan
     * warga persis sama dengan isi surat yang akan tercetak.
     *
     * @param  array<string, string>  $isian
     */
    public function teks(JenisSurat $jenisSurat, ?Pengajuan $pengajuan = null, array $isian = []): string
    {
        $xml = $this->documentXml($jenisSurat, $pengajuan, $isian);

        $doc = new \DOMDocument;
        $doc->loadXML($xml);

        $xp = new \DOMXPath($doc);
        $xp->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');

        $baris = [];

        foreach ($xp->query('//w:body/w:p | //w:body/w:tbl') as $simpul) {
            if ($simpul->nodeName === 'w:tbl') {
                // Kolom 1–2 pada tabel tanda tangan adalah spacer kosong;
                // yang dicetak hanya paragraf yang berisi teks.
                foreach ($xp->query('.//w:tr', $simpul) as $tr) {
                    foreach ($xp->query('w:tc', $tr) as $tc) {
                        foreach ($xp->query('w:p', $tc) as $p) {
                            $t = '';

                            foreach ($xp->query('.//w:t', $p) as $tt) {
                                $t .= $tt->textContent;
                            }

                            if (trim($t) !== '') {
                                $baris[] = trim($t);
                            }
                        }
                    }
                }

                continue;
            }

            // Pertahankan urutan tab dan teks di dalam paragraf. Versi lama
            // mengumpulkan semua tab di depan baris sehingga label seperti
            // "Nama : ..." kehilangan posisinya.
            $t = '';

            foreach ($xp->query('.//w:r/*', $simpul) as $n) {
                if ($n->nodeName === 'w:tab') {
                    $t .= "\t";
                } elseif ($n->nodeName === 'w:br') {
                    $t .= "\n";
                } elseif ($n->nodeName === 'w:t') {
                    $t .= $n->textContent;
                }
            }

            $baris[] = rtrim($t);
        }

        return trim((string) preg_replace("/\n{3,}/", "\n\n", implode("\n", $baris)));
    }

    /** Nama berkas unduhan yang enak dibaca petugas desa. */
    public function namaUnduhan(JenisSurat $jenisSurat, ?Pengajuan $pengajuan = null): string
    {
        $bagian = [$jenisSurat->nama, $pengajuan?->nama, $pengajuan?->nomor_referensi];

        $bersih = [];

        foreach ($bagian as $b) {
            $b = trim((string) preg_replace('/[^A-Za-z0-9]+/', '-', (string) $b), '-');

            if ($b !== '') {
                $bersih[] = $b;
            }
        }

        return implode('-', $bersih ?: ['surat']).'.docx';
    }

    /**
     * document.xml hasil penggantian teks.
     *
     * Memakai manipulasi string tepat sasaran (bukan DOMDocument yang
     * merapikan ulang XML) supaya seluruh atribut dan urutan elemen asli
     * tetap utuh.
     *
     * @param  array<string, string>  $isian
     */
    public function documentXml(JenisSurat $jenisSurat, ?Pengajuan $pengajuan = null, array $isian = []): string
    {
        $folder = $this->folderKerangka($jenisSurat);
        $xml = $this->bacaKerangka($folder, 'word/document.xml');

        if ($xml === null) {
            throw new \RuntimeException('Kerangka surat desa tidak ditemukan: '.$folder);
        }

        $nilai = PetaSuratDesa::nilai($pengajuan, $isian);
        $peta = PetaSuratDesa::peta((string) $jenisSurat->slug);

        // 1. Judul & nomor surat. Untuk tujuh kategori resmi, judul diambil
        //    persis dari dokumen desa; kategori lama memakai nama layanannya.
        $judul = \App\Support\TemplateSuratDesa::judul((string) $jenisSurat->slug)
            ?? mb_strtoupper((string) $jenisSurat->nama);

        $xml = $this->gantiParagraf($xml, 7, $judul);
        $xml = $this->gantiParagraf($xml, 8, $this->nomorSurat($pengajuan));

        // 2. Paragraf pembuka.
        $xml = $this->gantiParagraf($xml, 11, $this->pembuka());

        // 3. Baris identitas pemohon.
        foreach (($peta['identitas'] ?? []) as $baris) {
            $xml = $this->gantiTitikBerisi($xml, $baris['paragraf'], (string) ($nilai[$baris['kunci']] ?? ''));
        }

        // 4. Baris alamat desa & kabupaten (teks setelah titik dua).
        foreach (($peta['alamat_desa'] ?? []) as $paragraf) {
            $xml = $this->gantiSetelahTitikDua($xml, (int) $paragraf, (string) $nilai['alamat_desa']);
        }

        foreach (($peta['alamat_kabupaten'] ?? []) as $paragraf) {
            $xml = $this->gantiSetelahTitikDua($xml, (int) $paragraf, (string) $nilai['alamat_kabupaten']);
        }

        // 5. Blok identitas kedua (khusus Surat Keterangan Orang Tua Asuh).
        foreach (($peta['identitas_kedua'] ?? []) as $baris) {
            $xml = $this->gantiTitikBerisi($xml, $baris['paragraf'], (string) ($nilai[$baris['kunci']] ?? ''));
        }

        foreach (($peta['alamat_desa_kedua'] ?? []) as $paragraf) {
            $xml = $this->gantiSetelahTitikDua($xml, (int) $paragraf, (string) $nilai['alamat_desa']);
        }

        foreach (($peta['alamat_kabupaten_kedua'] ?? []) as $paragraf) {
            $xml = $this->gantiSetelahTitikDua($xml, (int) $paragraf, (string) $nilai['alamat_kabupaten']);
        }

        // 6. Titik-titik pada baris tambahan (usaha, meninggal, sejak).
        foreach (($peta['titik'] ?? []) as $baris) {
            $xml = $this->gantiTitikBerisi($xml, $baris['paragraf'], (string) ($nilai[$baris['kunci']] ?? ''));
        }

        // 7. Kata tetap pada kalimat yang harus diganti (pekerjaan).
        //    Diproses dari frasa paling kanan agar nilai yang disisipkan tidak
        //    tertukar dengan frasa lain pada paragraf yang sama.
        $daftarFrasa = $peta['frasa'] ?? [];

        usort($daftarFrasa, function (array $a, array $b) use ($xml) {
            $posisiA = $this->posisiFrasa($xml, (int) $a['paragraf'], (string) $a['cari']);
            $posisiB = $this->posisiFrasa($xml, (int) $b['paragraf'], (string) $b['cari']);

            return $posisiB <=> $posisiA;
        });

        foreach ($daftarFrasa as $frasa) {
            $nilaiFrasa = (string) ($nilai[$frasa['kunci']] ?? '');

            if ($nilaiFrasa !== '') {
                $xml = $this->gantiFrasa($xml, (int) $frasa['paragraf'], (string) $frasa['cari'], $nilaiFrasa);
            }
        }

        // 8. Blok tanda tangan: tempat/tanggal dan nama kepala desa.
        $xml = $this->gantiTandaTangan($xml, $pengajuan);

        // 9. Paling akhir: ganti blok kop lama dengan kop resmi desa.
        //    Dikerjakan terakhir supaya nomor paragraf di atas tetap mengacu
        //    ke kerangka asli.
        $xml = $this->pasangKop($xml);

        return $xml;
    }

    /** Folder kerangka untuk satu jenis surat. */
    private function folderKerangka(JenisSurat $jenisSurat): string
    {
        $folder = self::KERANGKA[(string) $jenisSurat->slug] ?? self::KERANGKA_BAKU;

        return is_dir(resource_path($folder)) ? $folder : self::KERANGKA_BAKU;
    }

    /**
     * Ganti paragraf kop pada kerangka dengan satu gambar kop resmi.
     *
     * Dokumen desa menyusun kop dari lambang (gambar melayang), tiga baris teks,
     * dan dua garis penghubung. Semuanya sudah menyatu di berkas
     * public/images/kop-surat.png, jadi keempat paragraf itu dibuang dan diganti
     * satu paragraf bergambar selebar kop aslinya.
     */
    private function pasangKop(string $xml): string
    {
        $kop = $this->kopPath();

        // Tanpa berkas kop, blok kop asli dibiarkan apa adanya agar surat tidak
        // kehilangan identitas desa.
        if ($kop === null) {
            return $xml;
        }

        $ukuran = @getimagesize($kop);

        if ($ukuran === false || $ukuran[0] <= 0) {
            return $xml;
        }

        $lebar = self::KOP_LEBAR;
        $tinggi = (int) round($lebar * $ukuran[1] / $ukuran[0]);

        $paragraf = $this->paragraf($xml);

        [$awalIndeks, $akhirIndeks] = self::KOP_PARAGRAF;

        if (! isset($paragraf[$awalIndeks - 1], $paragraf[$akhirIndeks - 1])) {
            return $xml;
        }

        $awal = $paragraf[$awalIndeks - 1][0];
        $akhir = $paragraf[$akhirIndeks - 1][1];

        $gambar = $this->paragrafKop($lebar, $tinggi);

        return substr($xml, 0, $awal).$gambar.substr($xml, $akhir);
    }

    /**
     * XML satu paragraf berisi gambar kop, dipusatkan seperti kop asli.
     */
    private function paragrafKop(int $lebar, int $tinggi): string
    {
        $id = self::KOP_RELASI;

        return '<w:p><w:pPr><w:spacing w:after="0" w:line="240" w:lineRule="auto"/>'
            .'<w:jc w:val="center"/></w:pPr><w:r><w:rPr><w:noProof/></w:rPr>'
            .'<w:drawing>'
            .'<wp:inline distT="0" distB="0" distL="0" distR="0">'
            .'<wp:extent cx="'.$lebar.'" cy="'.$tinggi.'"/>'
            .'<wp:effectExtent l="0" t="0" r="0" b="0"/>'
            .'<wp:docPr id="100" name="Kop Surat Desa"/>'
            .'<wp:cNvGraphicFramePr>'
            .'<a:graphicFrameLocks xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" noChangeAspect="1"/>'
            .'</wp:cNvGraphicFramePr>'
            .'<a:graphic xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main">'
            .'<a:graphicData uri="http://schemas.openxmlformats.org/drawingml/2006/picture">'
            .'<pic:pic xmlns:pic="http://schemas.openxmlformats.org/drawingml/2006/picture">'
            .'<pic:nvPicPr><pic:cNvPr id="100" name="Kop Surat Desa"/>'
            .'<pic:cNvPicPr><a:picLocks noChangeAspect="1"/></pic:cNvPicPr></pic:nvPicPr>'
            .'<pic:blipFill><a:blip r:embed="'.$id.'"/>'
            .'<a:stretch><a:fillRect/></a:stretch></pic:blipFill>'
            .'<pic:spPr><a:xfrm><a:off x="0" y="0"/><a:ext cx="'.$lebar.'" cy="'.$tinggi.'"/></a:xfrm>'
            .'<a:prstGeom prst="rect"><a:avLst/></a:prstGeom></pic:spPr>'
            .'</pic:pic></a:graphicData></a:graphic>'
            .'</wp:inline></w:drawing></w:r></w:p>';
    }

    /**
     * Ganti isi satu simpul <w:t> pada paragraf tertentu.
     *
     * Bila nilai cocok dengan run sebelumnya yang bertetangga (mis. " ………"
     * hasil pecahan Word), run itu dikosongkan agar tidak ada sisa teks.
     */
    private function gantiSel(string $xml, int $nomorParagraf, int|string $sel, string $teks): string
    {
        $paragraf = $this->paragraf($xml);

        if (! isset($paragraf[$nomorParagraf - 1])) {
            return $xml;
        }

        [$awal, $akhir] = $paragraf[$nomorParagraf - 1];
        $isiParagraf = substr($xml, $awal, $akhir - $awal);

        $simpul = $this->simpulTeks($isiParagraf);

        if ($simpul === []) {
            return $xml;
        }

        $indeks = $sel === 'akhir' ? count($simpul) - 1 : (int) $sel;

        if (! isset($simpul[$indeks])) {
            return $xml;
        }

        [$tAwal, $tAkhir] = $simpul[$indeks];

        $baru = substr($isiParagraf, 0, $tAwal)
            .$this->escape($teks)
            .substr($isiParagraf, $tAkhir);

        return substr($xml, 0, $awal).$baru.substr($xml, $akhir);
    }

    /**
     * Nilai yang akan dicetak pada satu baris isian.
     *
     * Baris yang titik duanya menempel pada titik-titik ("Agama  : …………")
     * kehilangan titik duanya saat titik-titik dikosongkan, sehingga titik dua
     * perlu ikut dicetak di depan nilai.
     */
    private function nilaiBarisIsian(string $xml, int $nomorParagraf, string $teks): string
    {
        $paragraf = $this->paragraf($xml);

        if (! isset($paragraf[$nomorParagraf - 1])) {
            return $teks;
        }

        [$awal, $akhir] = $paragraf[$nomorParagraf - 1];
        $isi = substr($xml, $awal, $akhir - $awal);

        // Apakah ada run yang berisi titik dua terpisah (" : " atau ":")?
        $adaTitikDua = preg_match('#<w:t\b[^>]*>\s*:\s*</w:t>#s', $isi) === 1;

        // Titik dua yang menempel pada titik-titik (": ……") akan hilang bersama
        // run pengisi, jadi perlu dicetak ulang di depan nilai. Titik dua pada
        // nilai titik-titik bawaan sudah membawa ":" sendiri, jadi tidak
        // ditambah lagi.
        $titikSudahBertitikDua = str_starts_with(ltrim($teks), ':');
        $menempel = preg_match('#<w:t\b[^>]*>\s*:\s*…+#su', $isi) === 1;

        if (! $adaTitikDua && $menempel && ! $titikSudahBertitikDua) {
            return ': '.$teks;
        }

        return $teks;
    }

    /**
     * Ganti isi baris isian berlabel.
     *
     * Dokumen desa memecah titik-titik menjadi beberapa run, dan baris
     * Agama/Status Perkawinan menyatukan titik dua dengan titik-titiknya.
     * Karena itu: (1) setiap run pengisi dikosongkan isinya, (2) satu run
     * baru berisi nilai disisipkan pada titik yang tepat —
     *      "Nama  : ........"  -> sisip sesudah run ": "
     *      "NIK   : ........"  -> sisip sesudah run ":"
     *      "Alamat: Dusun ...." -> sisip sesudah run ": Dusun "
     *      "Agama : ........"  -> sisip sesudah run "Agama" (label), karena
     *                             titik duanya menempel pada titik-titik.
     */
    private function gantiBarisIsian(string $xml, int $nomorParagraf, string $teks): string
    {
        $paragraf = $this->paragraf($xml);

        if (! isset($paragraf[$nomorParagraf - 1])) {
            return $xml;
        }

        [$awal, $akhir] = $paragraf[$nomorParagraf - 1];
        $isiParagraf = substr($xml, $awal, $akhir - $awal);

        // Kumpulkan seluruh run beserta isi teks dan posisinya.
        preg_match_all(
            '#<w:r\b[^>]*>(?:\s*<w:rPr>.*?</w:rPr>)?\s*((?:<w:tab/>)*)\s*(?:<w:t\b[^>]*>(.*?)</w:t>)?#s',
            $isiParagraf,
            $run,
            PREG_OFFSET_CAPTURE | PREG_SET_ORDER
        );

        $sisip = null;
        $rPr = '';
        $pengganti = [];

        foreach ($run as $r) {
            $teksLama = html_entity_decode($r[2][0] ?? '', ENT_QUOTES | ENT_XML1, 'UTF-8');
            $posisiAwal = $r[0][1];
            $posisiAkhir = $r[0][1] + strlen($r[0][0]);

            // Format huruf: dari run pengisi bila ada, kalau tidak dari run
            // pertama yang punya rPr di baris ini.
            if ($rPr === '' && preg_match('#<w:rPr>.*?</w:rPr>#s', $r[0][0], $rm) === 1) {
                $rPr = $rm[0];
            }

            $punyaTitik = str_contains($teksLama, '…');

            if ($punyaTitik) {
                // Run pengisi: kosongkan isinya, sisip di sini bila belum.
                if ($sisip === null) {
                    $sisip = $posisiAwal;
                }

                $pengganti[] = [$posisiAwal, $posisiAkhir, $this->kosongkanTeks($r[0][0])];

                continue;
            }

            // Sesudah run titik dua (" : " atau ":") = titik sisip yang wajar.
            if ($sisip === null && preg_match('/^\s*:\s*$/u', $teksLama) === 1) {
                $sisip = $posisiAkhir;
            }
        }

        // Belum ada titik dua terpisah (mis. ": Dusun "): sisip sesudah run
        // tekstual terakhir yang bukan pengisi.
        $adaTitikDua = collect($run)->contains(
            fn ($r) => preg_match('/^\s*:\s*$/u', html_entity_decode($r[2][0] ?? '', ENT_QUOTES | ENT_XML1, 'UTF-8')) === 1
        );

        if (! $adaTitikDua) {
            $sisip = null;

            foreach ($run as $r) {
                $teksLama = html_entity_decode($r[2][0] ?? '', ENT_QUOTES | ENT_XML1, 'UTF-8');

                if ($teksLama !== '' && ! str_contains($teksLama, '…')) {
                    $sisip = $r[0][1] + strlen($r[0][0]);
                }
            }
        }

        if ($sisip === null) {
            return $xml;
        }

        // Terapkan pengosongan run (dari belakang agar offset tetap sah).
        foreach (array_reverse($pengganti) as [$a, $b, $baru]) {
            $isiParagraf = substr($isiParagraf, 0, $a).$baru.substr($isiParagraf, $b);
        }

        $run = '<w:r>'.$rPr.'<w:t xml:space="preserve">'.$this->escape($teks).'</w:t></w:r>';

        $baru = substr($isiParagraf, 0, $sisip).$run.substr($isiParagraf, $sisip);

        return substr($xml, 0, $awal).$baru.substr($xml, $akhir);
    }

    /** Buang isi <w:t> pada satu run, tapi pertahankan tab dan formatnya. */
    private function kosongkanTeks(string $run): string
    {
        return (string) preg_replace('#<w:t\b[^>]*>.*?</w:t>#s', '', $run);
    }

    /** Ganti isi satu run (mis. ": Desa ..." pada baris alamat). */
    private function gantiSatuRun(string $xml, int $nomorParagraf, string $teks): string
    {
        return $teks === '' ? $xml : $this->gantiSel($xml, $nomorParagraf, 0, $teks);
    }

    /**
     * Ganti seluruh teks sebuah paragraf dengan satu run, mempertahankan
     * format run pertama (huruf, ukuran, tebal, garis bawah) dan pengaturan
     * paragrafnya (perataan, tab, jarak baris).
     */
    private function gantiParagraf(string $xml, int $nomor, string $teks): string
    {
        if ($teks === '') {
            return $xml;
        }

        $paragraf = $this->paragraf($xml);

        if (! isset($paragraf[$nomor - 1])) {
            return $xml;
        }

        [$awal, $akhir] = $paragraf[$nomor - 1];
        $isi = substr($xml, $awal, $akhir - $awal);

        if (preg_match('#^(<w:p\b[^>]*>)(<w:pPr>.*?</w:pPr>)?(.*?)(</w:p>)$#s', $isi, $m) !== 1) {
            return $xml;
        }

        $pPr = $m[2] ?? '';
        $badan = $m[3];

        // rPr dari run pertama dipakai lagi untuk run baru.
        $rPr = '';
        if (preg_match('#<w:r\b[^>]*>\s*(<w:rPr>.*?</w:rPr>)#s', $badan, $rm) === 1) {
            $rPr = $rm[1];
        }

        $run = '<w:r>'.$rPr.'<w:t xml:space="preserve">'.$this->escape($teks).'</w:t></w:r>';

        $baru = $m[1].$pPr.$run.'</w:p>';

        return substr($xml, 0, $awal).$baru.substr($xml, $akhir);
    }

    /**
     * Ganti titik-titik pada paragraf tertentu bila nilainya tersedia.
     *
     * Nilai kosong sengaja dibiarkan: dokumen desa yang belum diisi memang
     * mencetak titik-titik, jadi surat lama tidak kehilangan bentuk aslinya.
     */
    private function gantiTitikBerisi(string $xml, int $nomorParagraf, string $teks): string
    {
        $teks = trim($teks);

        return $teks === '' ? $xml : $this->gantiTitik($xml, $nomorParagraf, $teks);
    }

    /**
     * Ganti seluruh teks setelah titik dua pertama pada satu paragraf.
     *
     * Dipakai untuk baris " : Desa ... Kecamatan ..." dan
     * " : Kabupaten ...". Tab dan format run sebelum titik dua dipertahankan,
     * jadi posisi baris tetap sama dengan dokumen Word.
     */
    private function gantiSetelahTitikDua(string $xml, int $nomorParagraf, string $teks): string
    {
        $teks = trim($teks);

        if ($teks === '') {
            return $xml;
        }

        $paragraf = $this->paragraf($xml);

        if (! isset($paragraf[$nomorParagraf - 1])) {
            return $xml;
        }

        [$awal, $akhir] = $paragraf[$nomorParagraf - 1];
        $isi = substr($xml, $awal, $akhir - $awal);

        preg_match_all('#<w:t\b[^>]*>(.*?)</w:t>#s', $isi, $cocok, PREG_OFFSET_CAPTURE);

        $sudah = false;
        $pengganti = [];

        foreach ($cocok[1] as $i => [$isiNode, $posisi]) {
            if (! $sudah) {
                $titikDua = strpos($isiNode, ':');

                if ($titikDua === false) {
                    continue;
                }

                $sudah = true;
                $baru = substr($isiNode, 0, $titikDua + 1).' '.$teks;
            } else {
                $baru = '';
            }

            $pengganti[] = [$posisi, $posisi + strlen($isiNode), $this->escape($baru)];
        }

        if (! $sudah) {
            return $xml;
        }

        foreach (array_reverse($pengganti) as [$a, $b, $baru]) {
            $isi = substr($isi, 0, $a).$baru.substr($isi, $b);
        }

        return substr($xml, 0, $awal).$isi.substr($xml, $akhir);
    }

    /**
     * Posisi text sebuah frasa di dalam paragraf (setelah run digabung).
     * Dipakai hanya untuk mengurutkan penggantian dari kanan ke kiri.
     */
    private function posisiFrasa(string $xml, int $nomorParagraf, string $cari): int
    {
        $paragraf = $this->paragraf($xml);

        if (! isset($paragraf[$nomorParagraf - 1])) {
            return -1;
        }

        [$awal, $akhir] = $paragraf[$nomorParagraf - 1];
        $isi = substr($xml, $awal, $akhir - $awal);

        preg_match_all('#<w:t\b[^>]*>(.*?)</w:t>#s', $isi, $cocok);

        $gabung = html_entity_decode(implode('', $cocok[1]), ENT_QUOTES | ENT_XML1, 'UTF-8');
        $posisi = mb_strpos($gabung, $cari);

        return $posisi === false ? -1 : $posisi;
    }

    /**
     * Ganti frasa tetap yang terpecah menjadi beberapa run Word.
     *
     * Contoh pada Surat Keterangan Pekerjaan: "Buruh Harian Lepas" terbagi
     * menjadi run "Buruh" + " Harian Lepas.". Penggantian dilakukan pada teks
     * gabungan run, lalu sisa teks di kanan-kirinya dipertahankan.
     */
    private function gantiFrasa(string $xml, int $nomorParagraf, string $cari, string $teks): string
    {
        $paragraf = $this->paragraf($xml);

        if (! isset($paragraf[$nomorParagraf - 1])) {
            return $xml;
        }

        [$awal, $akhir] = $paragraf[$nomorParagraf - 1];
        $isi = substr($xml, $awal, $akhir - $awal);

        preg_match_all('#<w:t\b[^>]*>(.*?)</w:t>#s', $isi, $cocok, PREG_OFFSET_CAPTURE);

        $gabung = '';
        $node = [];

        foreach ($cocok[1] as $i => [$isiNode, $posisi]) {
            $node[] = [
                'i' => $i,
                'mulai' => strlen($gabung),
                'panjang' => strlen($isiNode),
                'posisi' => $posisi,
            ];
            $gabung .= $isiNode;
        }

        $posisiCari = strpos($gabung, $cari);

        if ($posisiCari === false) {
            return $xml;
        }

        $akhirCari = $posisiCari + strlen($cari);
        $pengganti = [];
        $pertama = true;

        foreach ($node as $n) {
            $nodeAwal = $n['mulai'];
            $nodeAkhir = $n['mulai'] + $n['panjang'];

            if ($nodeAkhir <= $posisiCari || $nodeAwal >= $akhirCari) {
                continue;
            }

            $isiNode = $cocok[1][$n['i']][0];
            $potongAwal = max(0, $posisiCari - $nodeAwal);
            $potongAkhir = min($n['panjang'], $akhirCari - $nodeAwal);

            $baru = substr($isiNode, 0, $potongAwal)
                .($pertama ? $this->escape($teks) : '')
                .substr($isiNode, $potongAkhir);

            $pengganti[] = [$n['posisi'], $n['posisi'] + strlen($isiNode), $baru];
            $pertama = false;
        }

        foreach (array_reverse($pengganti) as [$a, $b, $baru]) {
            $isi = substr($isi, 0, $a).$baru.substr($isi, $b);
        }

        return substr($xml, 0, $awal).$isi.substr($xml, $akhir);
    }

    /** Ganti titik-titik di dalam satu paragraf kalimat tanpa membuang teks
     * lainnya. Titik-titik pada dokumen desa terpecah menjadi beberapa run,
     * kadang bercampur titik biasa sebagai titik penutup kalimat.
     */
    private function gantiTitik(string $xml, int $nomorParagraf, string $teks): string
    {
        $paragraf = $this->paragraf($xml);

        if (! isset($paragraf[$nomorParagraf - 1])) {
            return $xml;
        }

        [$awal, $akhir] = $paragraf[$nomorParagraf - 1];
        $isi = substr($xml, $awal, $akhir - $awal);

        preg_match_all(
            '#<w:t\b[^>]*>(.*?)</w:t>#s',
            $isi,
            $cocok,
            PREG_OFFSET_CAPTURE | PREG_SET_ORDER
        );

        $pengganti = [];
        $sudah = false;
        $akhirFiller = 0;
        $fillerBerakhirTitik = false;

        foreach ($cocok as $m) {
            [$teksNode, $posisi] = $m[1];
            $polos = html_entity_decode($teksNode, ENT_QUOTES | ENT_XML1, 'UTF-8');

            if (preg_match('/[.…]{2,}/u', $polos, $filler, PREG_OFFSET_CAPTURE) !== 1) {
                continue;
            }

            // Teks sebelum titik-titik ("Agama: ………", "Alamat: Dusun ……")
            // dipertahankan apa adanya; hanya bagian titik-titik yang diganti.
            $awalFiller = (int) $filler[0][1];
            $awalan = $sudah ? '' : substr($polos, 0, $awalFiller);

            $pengganti[] = [
                $posisi,
                $posisi + strlen($teksNode),
                $sudah ? '' : $this->escape($awalan.$teks),
            ];

            $sudah = true;
            $akhirFiller = $posisi + strlen($teksNode);
            $fillerBerakhirTitik = str_ends_with(rtrim($polos), '.');
        }

        if (! $sudah) {
            return $xml;
        }

        // Bila titik-titik adalah akhir kalimat dan filler aslinya berakhir
        // dengan titik, pertahankan satu titik penutup ("…Sari." bukan "Sari..").
        if ($fillerBerakhirTitik && $teks !== '') {
            $sesudah = substr($isi, $akhirFiller);
            $sisaTeks = '';

            if (preg_match_all('#<w:t\b[^>]*>(.*?)</w:t>#s', $sesudah, $sisa) > 0) {
                $sisaTeks = trim(implode('', $sisa[1]));
            }

            if ($sisaTeks === '' && preg_match('/[.!?]$/u', trim($teks)) !== 1) {
                $pengganti[0][2] .= '.';
            }
        }

        foreach (array_reverse($pengganti) as [$a, $b, $baru]) {
            $isi = substr($isi, 0, $a).$baru.substr($isi, $b);
        }

        return substr($xml, 0, $awal).$isi.substr($xml, $akhir);
    }

    /** Nomor surat resmi: "Nomor : 470/ 001 /18.01.13.2019/XI/2026". */
    private function nomorSurat(?Pengajuan $pengajuan): string
    {
        $nomor = trim((string) ($pengajuan?->nomor_surat ?? ''));

        if ($nomor !== '') {
            return 'Nomor : '.$nomor;
        }

        // Belum diberi nomor: cetak pola kosong seperti dokumen desa.
        $kode = (string) (config('desa.kode_wilayah_surat') ?: '18.01.13.2019');
        $tanggal = $pengajuan?->created_at ?? now();

        return 'Nomor : 470/        /'.$kode.'/'.$this->romawi((int) $tanggal->format('n')).'/'.$tanggal->format('Y');
    }

    /** Paragraf pembuka, nama desa diambil dari profil. */
    private function pembuka(): string
    {
        $profil = SiteProfile::current();

        $desa = PetaSuratDesa::rapikanKata((string) $profil->nama_desa);
        $kecamatan = PetaSuratDesa::rapikanKata((string) $profil->kecamatan);
        $kabupaten = PetaSuratDesa::rapikanKata((string) $profil->kabupaten);

        // Dokumen desa selalu menulis "Kepala Desa ...". Bila profil hanya
        // menyimpan "Karang Sari", awalan "Desa" ditambahkan di sini.
        if ($desa !== '' && preg_match('/^(desa|kelurahan)\s/i', $desa) !== 1) {
            $desa = 'Desa '.$desa;
        }

        return 'Yang bertanda tangan dibawah ini, Kepala '.$desa.' '
            .'Kecamatan '.$kecamatan.' Kabupaten '.$kabupaten.' '
            .'menerangkan dengan sebenar-benarnya bahwa :';
    }

    /**
     * Nama desa tanpa awalan "Desa", dengan spasi dirapikan.
     *
     * Data lama bisa menulis "Desa KarangSari" (tanpa spasi). Agar kalimat
     * pembuka dan blok tanda tangan tetap enak dibaca, nama yang menempelkan
     * dua kata kapital tanpa spasi dirapikan menjadi "Karang Sari".
     */
    private function namaDesaBersih(): string
    {
        $desa = $this->tanpaAwalan(trim((string) SiteProfile::current()->nama_desa), ['Desa', 'Kelurahan']);

        return (string) preg_replace('/(?<=[a-z])(?=[A-Z])/u', ' ', $desa);
    }

    /**
     * Blok tanda tangan pada tabel 3 kolom: ganti tempat/tanggal dan jabatan.
     *
     * Dokumen desa memecah satu baris menjadi beberapa run ("Kepala" +
     * " Desa Karang Sari", "Karang Sari, 05 " + "November" + " 2026").
     * Karena itu teks tiap paragraf di dalam tabel digabung lebih dahulu,
     * lalu hanya simpul pertama yang diisi teks baru — format huruf dan
     * perataan paragraf tetap seperti aslinya.
     *
     * Nama kepala desa TIDAK diganti: pada semua dokumen resmi nama
     * "R O M S I, SH" tercetak hitam sebagai bagian tetap template. Nama
     * pejabat dari database tidak boleh menimpanya.
     */
    private function gantiTandaTangan(string $xml, ?Pengajuan $pengajuan): string
    {
        $tempat = $this->namaDesaBersih();
        $tanggal = ($pengajuan?->created_at ?? now())
            ->locale('id')
            ->translatedFormat('j F Y');

        preg_match_all('#<w:tbl>.*?</w:tbl>#s', $xml, $tabel, PREG_OFFSET_CAPTURE);

        // Kerjakan dari tabel terakhir agar offset tabel sebelumnya tetap sah.
        foreach (array_reverse($tabel[0]) as [$isiTabel, $posTabel]) {
            preg_match_all('#<w:p\b[^>]*>.*?</w:p>#s', $isiTabel, $paragraf, PREG_OFFSET_CAPTURE);

            $tabelBaru = $isiTabel;

            // Paragraf juga diproses dari belakang agar offsetnya tidak bergeser.
            foreach (array_reverse($paragraf[0]) as [$isiParagraf, $posParagraf]) {
                $panjangParagraf = strlen($isiParagraf);

                preg_match_all(
                    '#<w:t\b[^>]*>(.*?)</w:t>#s',
                    $isiParagraf,
                    $teks,
                    PREG_OFFSET_CAPTURE | PREG_SET_ORDER
                );

                if ($teks === []) {
                    continue;
                }

                $gabung = '';

                foreach ($teks as $t) {
                    $gabung .= $t[1][0];
                }

                $lama = trim(html_entity_decode($gabung, ENT_QUOTES | ENT_XML1, 'UTF-8'));

                $baru = match (true) {
                    // "Karang Sari, 05 November 2026"
                    (bool) preg_match('/^\p{L}[\p{L} .]*,\s*\d{1,2}\s+\p{L}+\s+\d{4}$/u', $lama) => ($tempat !== '' ? $tempat.', ' : '').$tanggal,
                    // "Kepala Desa Karang Sari"
                    str_starts_with($lama, 'Kepala Desa') => 'Kepala Desa '.$tempat,
                    default => null,
                };

                if ($baru === null) {
                    continue;
                }

                $pengganti = [];
                $pertama = true;

                foreach ($teks as $t) {
                    [$isiNode, $posNode] = $t[1];
                    $pengganti[] = [
                        $posNode,
                        $posNode + strlen($isiNode),
                        $pertama ? $this->escape($baru) : '',
                    ];
                    $pertama = false;
                }

                foreach (array_reverse($pengganti) as [$a, $b, $teksBaru]) {
                    $isiParagraf = substr($isiParagraf, 0, $a).$teksBaru.substr($isiParagraf, $b);
                }

                $tabelBaru = substr($tabelBaru, 0, $posParagraf)
                    .$isiParagraf
                    .substr($tabelBaru, $posParagraf + $panjangParagraf);
            }

            $xml = substr($xml, 0, $posTabel)
                .$tabelBaru
                .substr($xml, $posTabel + strlen($isiTabel));
        }

        return $xml;
    }

    /**
     * Posisi awal/akhir isi setiap <w:t> pada dokumen (atau pada satu paragraf).
     *
     * @return array<int, array{0: int, 1: int}>
     */
    private function simpulTeks(string $xml): array
    {
        $hasil = [];

        preg_match_all('#<w:t\b[^>]*>(.*?)</w:t>#s', $xml, $cocok, PREG_OFFSET_CAPTURE);

        foreach ($cocok[1] as $m) {
            $hasil[] = [$m[1], $m[1] + strlen($m[0])];
        }

        return $hasil;
    }

    /**
     * Posisi awal/akhir setiap paragraf tingkat atas <w:body> (paragraf di
     * dalam tabel tidak dihitung), berurutan.
     *
     * @return array<int, array{0: int, 1: int}>
     */
    private function paragraf(string $xml): array
    {
        $awalBody = strpos($xml, '<w:body>');
        $akhirBody = strpos($xml, '</w:body>');

        if ($awalBody === false || $akhirBody === false) {
            return [];
        }

        $body = substr($xml, $awalBody, $akhirBody - $awalBody);

        // Ganti isi tabel dengan penanda sepanjang isinya supaya posisi
        // paragraf lain tidak bergeser.
        $tanpaTabel = (string) preg_replace_callback(
            '#<w:tbl>.*?</w:tbl>#s',
            fn (array $m) => str_repeat(' ', strlen($m[0])),
            $body
        );

        preg_match_all('#<w:p\b[^>]*>.*?</w:p>#s', $tanpaTabel, $cocok, PREG_OFFSET_CAPTURE);

        $hasil = [];

        foreach ($cocok[0] as $m) {
            $hasil[] = [$awalBody + $m[1], $awalBody + $m[1] + strlen($m[0])];
        }

        return $hasil;
    }

    /** Baca satu bagian dari kerangka dokumen desa. */
    private function bacaKerangka(string $folder, string $bagian): ?string
    {
        $path = resource_path($folder.'/'.$bagian);

        return is_file($path) ? (string) file_get_contents($path) : null;
    }

    /** Escape untuk isi XML. */
    private function escape(string $teks): string
    {
        return htmlspecialchars($teks, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }

    /** Bulan ke angka Romawi (untuk nomor surat). */
    private function romawi(int $bulan): string
    {
        return [
            1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV', 5 => 'V', 6 => 'VI',
            7 => 'VII', 8 => 'VIII', 9 => 'IX', 10 => 'X', 11 => 'XI', 12 => 'XII',
        ][$bulan] ?? 'I';
    }

    /** Buang awalan "Desa"/"Kabupaten" agar tidak tercetak dua kali. */
    private function tanpaAwalan(string $teks, array $awalan): string
    {
        foreach ($awalan as $a) {
            if (mb_strtolower(mb_substr($teks, 0, mb_strlen($a) + 1)) === mb_strtolower($a).' ') {
                return trim(mb_substr($teks, mb_strlen($a) + 1));
            }
        }

        return $teks;
    }
}
