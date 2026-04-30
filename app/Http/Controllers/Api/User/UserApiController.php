<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Api\ApiController;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;

abstract class UserApiController extends ApiController
{
    /**
     * Return the user associated with the current API key.
     */
    protected function apiUser(): User
    {
        return request()->attributes->get('api_user');
    }

    /**
     * Abort with a 403 JSON error if the API key lacks the given permission.
     * An empty permissions array means "all access" (same behaviour as admin keys).
     */
    protected function checkPermission(string $permission): void
    {
        $permissions = request()->attributes->get('api_key_permissions', []);

        if (!empty($permissions) && !in_array('user.' . $permission, $permissions)) {
            abort(response()->json([
                'error' => [
                    'code' => 'FORBIDDEN',
                    'message' => 'This API key does not have the required permission: user.' . $permission,
                ],
            ], 403));
        }
    }

    /**
     * Build a standardised error response.
     */
    protected function apiError(string $code, string $message, int $status = 400, array $details = []): JsonResponse
    {
        $body = ['error' => ['code' => $code, 'message' => $message]];

        if (!empty($details)) {
            $body['error']['details'] = $details;
        }

        return response()->json($body, $status);
    }

    /**
     * Determine which relationship includes are permitted for the current key.
     * An empty permissions array means all includes are allowed.
     */
    protected function userAllowedIncludes(array $includes = []): array
    {
        $permissions = request()->attributes->get('api_key_permissions', []);
        $registeredPermissions = Arr::dot(config('permissions.api.user', []));
        $allowedIncludes = [];

        foreach ($includes as $include) {
            $relation = self::MAPPED_INCLUDES[$include] ?? $include;
            $permKey = 'user.' . $relation . '.view';

            if (
                empty($permissions) ||
                in_array($permKey, $permissions) ||
                !array_key_exists($permKey, $registeredPermissions)
            ) {
                $allowedIncludes[] = $include;
            }
        }

        return $allowedIncludes;
    }
}
