<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Http\Request;
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
            // Decode and verify JWT signature.
            $jwtSecret = config('services.supabase.jwt_secret');

            if (empty($jwtSecret)) {
                Log::error('SUPABASE_JWT_SECRET is not configured.');
                return response()->json(['message' => 'Autentiseringsfeil.'], 500);
            }

            $decoded = JWT::decode($token, new Key($jwtSecret, 'HS256'));

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
}
