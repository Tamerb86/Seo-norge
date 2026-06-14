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
            // Decode and verify JWT token
            $jwtSecret = config('services.supabase.jwt_secret');
            $decoded = JWT::decode($token, new Key($jwtSecret, 'HS256'));

            // Extract user ID from token
            $userId = $decoded->sub ?? null;
            $email = $decoded->email ?? null;

            if (!$userId) {
                return response()->json([
                    'message' => 'Ugyldig token.',
                ], 401);
            }

            // Find or create user in our database
            $user = User::firstOrCreate(
                ['id' => $userId],
                [
                    'email' => $email,
                    'plan' => 'free',
                ]
            );

            // Update email if changed
            if ($user->email !== $email) {
                $user->update(['email' => $email]);
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
