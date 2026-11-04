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
                "img-src 'self' data:",
                "style-src 'self' 'unsafe-inline'",
                "script-src 'self' 'unsafe-inline'",
                "font-src 'self' data:",
                "form-action 'self'",
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
