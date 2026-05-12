<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tujuan: Memasang security headers pada semua response web untuk defense-in-depth.
 * Caller: Terdaftar di bootstrap/app.php -> $middleware->web(append: [...SecurityHeaders::class]).
 * Dependensi: Tidak ada.
 * Main Functions: handle($request, $next) — set headers setelah response dibentuk.
 * Side Effects: Menambah header HTTP response (tidak mengubah body).
 *
 * Headers yang dipasang:
 * - X-Frame-Options: SAMEORIGIN       (mitigasi clickjacking)
 * - X-Content-Type-Options: nosniff   (mitigasi MIME sniffing)
 * - Referrer-Policy: strict-origin-when-cross-origin (kurangi kebocoran referrer)
 * - Strict-Transport-Security         (hanya di-set bila request pakai HTTPS; hindari lock-out saat dev HTTP)
 *
 * Catatan: Content-Security-Policy tidak di-set di sini karena butuh tuning terpisah
 * (view memakai inline Alpine handler, inline onclick di posts/show, external font). Tambahkan di iterasi berikutnya.
 * X-XSS-Protection sengaja tidak ditambah — header ini sudah deprecated di browser modern.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }
}
