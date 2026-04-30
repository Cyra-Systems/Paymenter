<?php

use App\Http\Middleware\Api\AdminApi;
use App\Http\Middleware\Api\UserApi;
use App\Http\Middleware\CheckoutParameterMiddleware;
use App\Http\Middleware\EnsureUserHasPermissions;
use App\Http\Middleware\ImpersonateMiddleware;
use App\Http\Middleware\LockSession;
use App\Http\Middleware\ProxyMiddleware;
use App\Http\Middleware\ResolveUserSession;
use App\Http\Middleware\SetLocale;
use App\Models\DebugLog;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Laravel\Passport\Http\Middleware\CheckForAnyScope;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->append(ProxyMiddleware::class);
        $middleware->alias([
            'has' => EnsureUserHasPermissions::class,
            'scope' => CheckForAnyScope::class,
            'api.admin' => AdminApi::class,
            'api.user' => UserApi::class,
            'checkout' => CheckoutParameterMiddleware::class,
        ]);
        $middleware->web([
            ResolveUserSession::class,
            LockSession::class,
            ImpersonateMiddleware::class,
            SetLocale::class,
        ]);

        RateLimiter::for('user-api', function (Request $request) {
            $key = $request->attributes->get('api_key');
            if (!$key || !$key->rate_limit) {
                return Limit::none();
            }

            return Limit::perMinute($key->rate_limit)->by('user-api-key:' . $key->id)->response(function () {
                return response()->json([
                    'error' => [
                        'code' => 'RATE_LIMIT_EXCEEDED',
                        'message' => 'Too many requests. Please slow down.',
                    ],
                ], 429);
            });
        });
    })
    ->withEvents(discover: [
        __DIR__ . '/../app/Extensions',
        __DIR__ . '/../app/Listeners',
    ])
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->report(function (Exception $exception) {
            try {
                if (!config('settings.debug', false)) {
                    return;
                }
                DebugLog::create([
                    'type' => 'exception',
                    'context' => [
                        'message' => $exception->getMessage(),
                        'file' => $exception->getFile(),
                        'line' => $exception->getLine(),
                        'trace' => $exception->getTraceAsString(),
                    ],
                ]);
            } catch (Exception $e) {
                throw $e;
            }
        });

        // Return standardised JSON error envelopes for all v1/user/* API routes
        $exceptions->render(function (\Illuminate\Validation\ValidationException $e, Request $request) {
            if ($request->is('api/v1/user/*')) {
                return response()->json([
                    'error' => [
                        'code' => 'VALIDATION_ERROR',
                        'message' => 'The given data was invalid.',
                        'details' => $e->errors(),
                    ],
                ], 422);
            }
        });

        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\HttpException $e, Request $request) {
            if ($request->is('api/v1/user/*')) {
                $code = match ($e->getStatusCode()) {
                    401 => 'UNAUTHENTICATED',
                    403 => 'FORBIDDEN',
                    404 => 'NOT_FOUND',
                    405 => 'METHOD_NOT_ALLOWED',
                    422 => 'VALIDATION_ERROR',
                    429 => 'RATE_LIMIT_EXCEEDED',
                    500 => 'SERVER_ERROR',
                    default => 'SERVER_ERROR',
                };

                return response()->json([
                    'error' => [
                        'code' => $code,
                        'message' => $e->getMessage() ?: 'An error occurred.',
                    ],
                ], $e->getStatusCode());
            }
        });
    })->create();
