<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Browser protection headers on every web response (pages, downloads, PDFs):
 * - X-Frame-Options / frame-ancestors: no other site can show these pages in a frame (clickjacking).
 * - X-Content-Type-Options: browsers do not guess a file's type (an uploaded file is never run as a script).
 * - Referrer-Policy: other sites see only the domain, never a full URL with ids in it.
 * - Permissions-Policy: camera, microphone, location and payment are off for every page.
 * - Strict-Transport-Security: on HTTPS only, the browser refuses plain HTTP for a year.
 * - Content-Security-Policy: scripts, styles and fonts load only from this site and the hosts in config/security.php.
 *   Sent as Report-Only until config('security.csp_enforce') is true.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (function_exists('header_remove') && ! headers_sent()) {
            header_remove('X-Powered-By'); // do not advertise the PHP version
        }

        $headers = $response->headers;
        $headers->set('X-Frame-Options', 'SAMEORIGIN');
        $headers->set('X-Content-Type-Options', 'nosniff');
        $headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=(), usb=()');

        if ($request->isSecure() && config('security.hsts_max_age') > 0) {
            $headers->set('Strict-Transport-Security', 'max-age='.(int) config('security.hsts_max_age').'; includeSubDomains');
        }

        // The policy governs pages (where scripts run). Files such as PDFs and certificate images, which open in the
        // browser's own viewer inside the site's frames, keep nosniff and SAMEORIGIN but no CSP that could stop the viewer.
        $type = (string) $headers->get('Content-Type', 'text/html');
        if ($type === '' || str_starts_with(strtolower($type), 'text/html')) {
            $headers->set(
                config('security.csp_enforce') ? 'Content-Security-Policy' : 'Content-Security-Policy-Report-Only',
                self::policy()
            );
        }

        return $response;
    }

    public static function policy(): string
    {
        $hosts = (array) config('security.csp_hosts', []);
        $dev = self::viteDevServer();
        $list = fn (array $extra) => trim(implode(' ', array_filter($extra)));

        $directives = [
            'default-src' => "'self'",
            // 'unsafe-inline' stays until the inline scripts and handlers in the views move to files.
            'script-src' => $list(["'self'", "'unsafe-inline'", ...($hosts['script'] ?? []), $dev]),
            'style-src' => $list(["'self'", "'unsafe-inline'", ...($hosts['style'] ?? []), $dev]),
            'font-src' => $list(["'self'", 'data:', ...($hosts['font'] ?? [])]),
            'img-src' => "'self' data: blob:",
            'connect-src' => $list(["'self'", ...self::pusherHosts(), $dev, $dev ? str_replace(['http://', 'https://'], ['ws://', 'wss://'], $dev) : null]),
            'frame-src' => "'self' blob:",
            'media-src' => "'self'",
            'object-src' => "'none'",
            'base-uri' => "'self'",
            'form-action' => "'self'",
            'frame-ancestors' => "'self'",
        ];

        return implode('; ', array_map(fn ($name, $value) => $name.' '.$value, array_keys($directives), $directives));
    }

    /** Live notifications and chat connect to Pusher (resources/js/bootstrap.js): its socket and fallback hosts. */
    private static function pusherHosts(): array
    {
        if (config('broadcasting.default') !== 'pusher') {
            return [];
        }
        $cluster = preg_replace('/[^a-z0-9-]/', '', strtolower((string) config('broadcasting.connections.pusher.options.cluster', 'mt1'))) ?: 'mt1';

        return ["wss://ws-{$cluster}.pusher.com", "https://sockjs-{$cluster}.pusher.com", 'https://stats.pusher.com'];
    }

    /** While `npm run dev` runs locally, Vite serves scripts and styles from its own address (public/hot). */
    private static function viteDevServer(): ?string
    {
        if (! app()->environment('local') || ! is_file(public_path('hot'))) {
            return null;
        }
        $url = trim((string) @file_get_contents(public_path('hot')));

        return preg_match('#^https?://[^/\s]+#', $url, $match) ? $match[0] : null;
    }
}
