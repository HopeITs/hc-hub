<?php

namespace App\Http\Middleware;

use App\Support\Turnstile;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * Apply browser security controls to every application response.
     */
    public function handle(Request $request, Closure $next): Response
    {
        Vite::useCspNonce();

        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
        $response->headers->set('Cross-Origin-Opener-Policy', 'same-origin');
        $response->headers->set('Cross-Origin-Resource-Policy', 'same-origin');

        // The digital ID card is deliberately reachable without a session, so
        // robots.txt (advisory, and ignored by the crawlers that matter) is not
        // enough — an indexed card would make a bearer-token URL searchable.
        // X-Robots-Tag is honoured per response by Google and Bing.
        if ($request->routeIs('digital-id.*')) {
            $response->headers->set('X-Robots-Tag', 'noindex, nofollow, noarchive, noimageindex');
        }

        if (config('security-headers.hsts.enabled')) {
            $response->headers->set(
                'Strict-Transport-Security',
                config('security-headers.hsts.value'),
            );
        }

        $csp = $this->contentSecurityPolicy(Vite::cspNonce(), $this->turnstileOrigin($request));
        $cspHeader = config('security-headers.csp.report_only')
            ? 'Content-Security-Policy-Report-Only'
            : 'Content-Security-Policy';

        $response->headers->set($cspHeader, $csp);

        return $response;
    }

    private function contentSecurityPolicy(string $nonce, ?string $turnstileOrigin = null): string
    {
        $directives = config('security-headers.csp.directives');
        $directives['script-src'][] = "'nonce-{$nonce}'";

        if ($turnstileOrigin !== null) {
            $directives['script-src'][] = $turnstileOrigin;
            $directives['frame-src'] = ["'self'", $turnstileOrigin];
        }

        return collect($directives)
            ->map(fn (array $sources, string $directive) => trim(
                $directive.' '.implode(' ', array_unique($sources)),
            ))
            ->implode('; ');
    }

    /**
     * Cloudflare's origin for the Turnstile widget, on the login page only
     * and only while the human check is on.
     */
    private function turnstileOrigin(Request $request): ?string
    {
        return Turnstile::isEnabled() && $request->routeIs('login') ? Turnstile::Origin : null;
    }
}
