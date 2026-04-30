<?php

namespace App\Http\Middleware\Api;

use App\Models\ApiKey;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class UserApi
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request):Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!config('settings.user_api_enabled', true)) {
            return response()->json([
                'error' => [
                    'code'    => 'SERVICE_UNAVAILABLE',
                    'message' => 'The User API is currently disabled.',
                ],
            ], 503);
        }

        if (!$request->bearerToken()) {
            return response()->json([
                'error' => [
                    'code' => 'UNAUTHENTICATED',
                    'message' => 'The request is missing a valid bearer token.',
                ],
            ], 401);
        }

        $token = ApiKey::where('token', hash('sha256', $request->bearerToken()))
            ->where('enabled', true)
            ->first();

        if (!$token) {
            return response()->json([
                'error' => [
                    'code' => 'UNAUTHENTICATED',
                    'message' => 'The provided API key is invalid or has been disabled.',
                ],
            ], 401);
        }

        if ($token->type !== 'user') {
            return response()->json([
                'error' => [
                    'code' => 'FORBIDDEN',
                    'message' => 'This API key does not have access to the user API.',
                ],
            ], 403);
        }

        if ($token->ip_addresses && !in_array($request->ip(), $token->ip_addresses)) {
            return response()->json([
                'error' => [
                    'code' => 'FORBIDDEN',
                    'message' => 'Your IP address is not allowed to use this API key.',
                ],
            ], 403);
        }

        if (!$token->user_id) {
            return response()->json([
                'error' => [
                    'code' => 'FORBIDDEN',
                    'message' => 'This API key is not associated with a user account.',
                ],
            ], 403);
        }

        $user = User::find($token->user_id);

        if (!$user) {
            return response()->json([
                'error' => [
                    'code' => 'FORBIDDEN',
                    'message' => 'The user associated with this API key no longer exists.',
                ],
            ], 403);
        }

        $token->last_used_at = now();
        $token->save();

        $request->attributes->set('api_key', $token);
        $request->attributes->set('api_key_permissions', $token->permissions ?? []);
        $request->attributes->set('api_user', $user);

        return $next($request);
    }
}
