<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Header keamanan dasar untuk semua respons web.
 *
 * CSP sengaja mengizinkan 'unsafe-inline' untuk style/script karena tampilan
 * prototipe desa memakai banyak atribut style dan script inline. Frame,
 * sniffing MIME, dan referrer tetap dikunci.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $headers = [
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'DENY',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            'Permissions-Policy' => 'geolocation=(), microphone=(), camera=()',
            'Content-Security-Policy' => implode('; ', [
                "default-src 'self'",
                // blob: diperlukan untuk pratinjau foto sebelum diunggah
                // (URL.createObjectURL pada form perangkat desa).
                "img-src 'self' data: blob:",
                "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com",
                "script-src 'self' 'unsafe-inline'",
                // Google Fonts memuat berkas font dari domain fonts.gstatic.com.
                "font-src 'self' data: https://fonts.gstatic.com",
                "connect-src 'self'",
                "form-action 'self'",
                // Peta lokasi memakai sematan Google Maps (tanpa API key),
                // jadi frame dari domain Google harus diizinkan.
                "frame-src https://www.google.com https://maps.google.com https://www.google.co.id",
                "frame-ancestors 'none'",
                "base-uri 'self'",
                "object-src 'none'",
            ]),
        ];

        // HSTS hanya aman dikirim lewat HTTPS; jangan set di server lokal.
        if ($request->isSecure()) {
            $headers['Strict-Transport-Security'] = 'max-age=31536000; includeSubDomains';
        }

        foreach ($headers as $nama => $nilai) {
            // Jangan menimpa bila sudah diset (mis. oleh proxy/CDN).
            if (! $response->headers->has($nama)) {
                $response->headers->set($nama, $nilai);
            }
        }

        return $response;
    }
}
