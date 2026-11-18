<?php

namespace App\Http\Controllers;

use App\Models\JenisSurat;
use Illuminate\Http\Response;

/**
 * Berkas bantu mesin pencari untuk situs publik desa.
 *
 * Hanya halaman publik yang boleh diindeks. Panel admin, halaman formulir
 * pengajuan, dan halaman hasil/revisi sengaja tidak dimasukkan karena berisi
 * data pribadi warga atau hanya berguna setelah mengisi formulir.
 */
class SitemapController extends Controller
{
    /** Halaman statis yang selalu ada. */
    protected const HALAMAN_TETAP = [
        '/' => '1.0',
        '/pelayanan' => '0.9',
        '/profil' => '0.8',
        '/informasi' => '0.8',
        '/potensi' => '0.7',
        '/panduan' => '0.7',
        '/kontak' => '0.6',
        '/cek-status' => '0.5',
        '/kebijakan-privasi' => '0.2',
        '/pemberitahuan-data' => '0.2',
    ];

    public function sitemap(): Response
    {
        $urls = [];

        foreach (self::HALAMAN_TETAP as $path => $prioritas) {
            $urls[] = [
                'loc' => url($path),
                'prioritas' => $prioritas,
                'diubah' => null,
            ];
        }

        // Hanya layanan aktif yang tampil di katalog publik.
        foreach (JenisSurat::query()->aktif()->terurut()->get() as $jenis) {
            $urls[] = [
                'loc' => route('layanan.detail', $jenis),
                'prioritas' => '0.8',
                'diubah' => $jenis->updated_at,
            ];
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";

        foreach ($urls as $u) {
            $xml .= '  <url>'."\n";
            $xml .= '    <loc>'.e($u['loc']).'</loc>'."\n";

            if ($u['diubah'] !== null) {
                $xml .= '    <lastmod>'.$u['diubah']->toAtomString().'</lastmod>'."\n";
            }

            $xml .= '    <priority>'.$u['prioritas'].'</priority>'."\n";
            $xml .= '  </url>'."\n";
        }

        $xml .= '</urlset>'."\n";

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    public function robots(): Response
    {
        $baris = [
            'User-agent: *',
            'Allow: /',
            '',
            '# Panel admin dan area berisi data pribadi warga tidak untuk diindeks.',
            'Disallow: /admin',
            'Disallow: /pengajuan',
            'Disallow: /cek-status',
            '',
            'Sitemap: '.url('/sitemap.xml'),
            '',
        ];

        return response(implode("\n", $baris), 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }
}
