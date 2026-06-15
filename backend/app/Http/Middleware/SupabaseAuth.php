<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Firebase\JWT\JWT;
use Firebase\JWT\JWK;
use Firebase\JWT\Key;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class SupabaseAuth
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json([
                'message' => 'Autentisering kreves.',
            ], 401);
        }

        try {
            // Verify JWT signature. Supabase has migrated to asymmetric signing
            // keys (ES256, published as JWKS); legacy projects still use the
            // HS256 shared secret. We pick the verifier based on the token's
            // `alg` header so both work during/after the rotation window.
            $decoded = $this->verifyToken($token);

            // Verify audience: Supabase user tokens carry aud="authenticated".
            // Without this, ANY token signed with the same secret (e.g. a
            // service-role or anon token) would be accepted as a user.
            if (($decoded->aud ?? null) !== 'authenticated' || ($decoded->role ?? null) !== 'authenticated') {
                return response()->json(['message' => 'Ugyldig token.'], 401);
            }

            // Verify issuer matches THIS Supabase project.
            $expectedIssuer = rtrim((string) config('services.supabase.url'), '/') . '/auth/v1';
            if (!empty(config('services.supabase.url')) && ($decoded->iss ?? null) !== $expectedIssuer) {
                return response()->json(['message' => 'Ugyldig token-utsteder.'], 401);
            }

            // Extract user ID from token
            $userId = $decoded->sub ?? null;
            $email = $decoded->email ?? null;

            if (!$userId) {
                return response()->json([
                    'message' => 'Ugyldig token.',
                ], 401);
            }

            // Find or create user in our database. `id` and `plan` are NOT
            // mass-assignable, so set them via explicit property assignment.
            // New users always start on the free plan; `plan` is otherwise
            // only ever changed server-side by the Stripe webhook.
            $user = User::find($userId);

            if (!$user) {
                $user = new User();
                $user->id = $userId;
                $user->email = $email;
                $user->plan = 'free';
                $user->save();
            } elseif ($email && $user->email !== $email) {
                // Keep the local email in sync with Supabase.
                $user->email = $email;
                $user->save();
            }

            // Set user on request
            $request->setUserResolver(function () use ($user) {
                return $user;
            });

            return $next($request);

        } catch (\Firebase\JWT\ExpiredException $e) {
            return response()->json([
                'message' => 'Token har utløpt. Vennligst logg inn på nytt.',
            ], 401);
        } catch (\Firebase\JWT\SignatureInvalidException $e) {
            return response()->json([
                'message' => 'Ugyldig token-signatur.',
            ], 401);
        } catch (\Exception $e) {
            Log::error('Auth error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'Autentiseringsfeil.',
            ], 401);
        }
    }

    /**
     * Verify a Supabase JWT, selecting the algorithm from its header.
     * ES256/RS256 -> verify against the project JWKS (asymmetric, current).
     * HS256       -> verify against the shared secret (legacy / rotation window).
     */
    private function verifyToken(string $token): object
    {
        $segments = explode('.', $token);
        if (count($segments) !== 3) {
            throw new \UnexpectedValueException('Malformed JWT.');
        }

        $header = json_decode(JWT::urlsafeB64Decode($segments[0]));
        $alg = $header->alg ?? null;

        if ($alg === 'HS256') {
            $secret = config('services.supabase.jwt_secret');
            if (empty($secret)) {
                throw new \RuntimeException('SUPABASE_JWT_SECRET is not configured for HS256 tokens.');
            }
            return JWT::decode($token, new Key($secret, 'HS256'));
        }

        // Asymmetric algorithms (ES256 / RS256) — verify against the JWKS.
        // firebase/php-jwt selects the right key by the token's `kid`.
        return JWT::decode($token, $this->getJwksKeys());
    }

    /**
     * Fetch and cache the project's public signing keys (JWKS).
     *
     * @return array<string, Key>
     */
    private function getJwksKeys(): array
    {
        $baseUrl = rtrim((string) config('services.supabase.url'), '/');
        if ($baseUrl === '') {
            throw new \RuntimeException('SUPABASE_URL is not configured (required for JWKS verification).');
        }

        $jwks = Cache::remember('supabase_jwks', now()->addHour(), function () use ($baseUrl) {
            $response = Http::timeout(10)->get($baseUrl . '/auth/v1/.well-known/jwks.json');
            return $response->successful() ? $response->json() : null;
        });

        if (empty($jwks['keys'])) {
            Cache::forget('supabase_jwks'); // don't cache a failed fetch
            throw new \RuntimeException('Unable to fetch Supabase JWKS.');
        }

        return JWK::parseKeySet($jwks);
    }
}
