<?php

namespace Paymenter\Extensions\Others\ReferralSystem\Listeners;

use App\Events\Order\Created;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;
use Paymenter\Extensions\Others\ReferralSystem\Models\ReferralCode;
use Paymenter\Extensions\Others\ReferralSystem\Models\ReferralOrder;

class AssociateOrderWithReferral
{
    public function handle(Created $event): void
    {
        $referralCode = Cookie::get('referral_code');

        if (!$referralCode) {
            return;
        }

        $code = ReferralCode::query()
            ->whereRaw('LOWER(code) = ?', [Str::lower($referralCode)])
            ->first();

        if (!$code || !$code->isActive()) {
            return;
        }

        // Don't allow self-referral 76b5ac1f725a0421abcd49d9b58aeabe
        if ($code->user_id === $event->order->user_id) {
            return;
        }

        // Check if association already exists 76b5ac1f725a0421abcd49d9b58aeabe
        if (ReferralOrder::where('order_id', $event->order->id)->exists()) {
            return;
        }

        ReferralOrder::create([
            'order_id' => $event->order->id,
            'referral_code_id' => $code->id,
        ]);
    }
}
