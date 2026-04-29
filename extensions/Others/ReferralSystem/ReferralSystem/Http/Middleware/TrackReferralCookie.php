<?php

namespace Paymenter\Extensions\Others\ReferralSystem\Http\Middleware;

use App\Helpers\ExtensionHelper;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;
use Paymenter\Extensions\Others\ReferralSystem\Models\ReferralCode;
use Symfony\Component\HttpFoundation\Response;

class TrackReferralCookie
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request):Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $codeParam = (string) $request->query('ref', '');

        if ($codeParam === '') {
            return $next($request);
        }

        $code = ReferralCode::query()
            ->whereRaw('LOWER(code) = ?', [Str::lower($codeParam)])
            ->first();

        // Skip if code not found, is suspended, or belongs to current user
        if (!$code || !$code->isActive() || $code->user_id === optional($request->user())->id) {
            return $next($request);
        }

        $extension = ExtensionHelper::getExtension('other', 'ReferralSystem');
        $cookieDays = (int) $extension->config('referral_cookie_days');

        if ($cookieDays === 0) {
            if (Cookie::has('referral_code')) {
                Cookie::queue(Cookie::forget('referral_code'));
            }

            return $next($request);
        }

        if (!Cookie::has('referral_code') || Cookie::get('referral_code') !== $code->code) {
            $code->increment('clicks_count');
        }

        Cookie::queue('referral_code', $code->code, $cookieDays * 1440);

        return $next($request);
    }
}
