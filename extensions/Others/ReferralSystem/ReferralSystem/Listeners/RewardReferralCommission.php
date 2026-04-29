<?php

namespace Paymenter\Extensions\Others\ReferralSystem\Listeners;

use App\Events\Invoice\Paid as InvoicePaid;
use App\Models\Service;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;
use Paymenter\Extensions\Others\ReferralSystem\Models\ReferralCode;
use Paymenter\Extensions\Others\ReferralSystem\Models\ReferralCommission;
use Paymenter\Extensions\Others\ReferralSystem\Models\ReferralOrder;
use Paymenter\Extensions\Others\ReferralSystem\Services\ReferralNotifier;

class RewardReferralCommission
{
    public function handle(InvoicePaid $event): void
    {
        $invoice = $event->invoice->loadMissing(['items.reference']);

        foreach ($invoice->items as $item) {
            if (!$item->reference instanceof Service) {
                continue;
            }

            $service = $item->reference;
            $codeCandidate = $this->resolveReferralCodeForService($service);

            // Skip if no referral code found
            if (!$codeCandidate) {
                continue;
            }

            if (
                !$codeCandidate->isActive()
                || $codeCandidate->user_id === $service->user_id
            ) {
                continue;
            }

            $result = DB::transaction(function () use ($invoice, $item, $service, $codeCandidate) {
                $code = ReferralCode::query()
                    ->with('packageOverrides')
                    ->lockForUpdate()
                    ->find($codeCandidate->id);

                if (
                    !$code
                    || !$code->isActive()
                    || $code->user_id === $service->user_id
                ) {
                    return;
                }

                $awardSignature = self::awardSignature($item->id);

                if (self::invoiceItemAlreadyRewarded($item->id)) {
                    return;
                }

                if (ReferralCommission::query()
                    ->where('award_signature', $awardSignature)
                    ->exists()) {
                    return;
                }

                $productId = $service->product_id;

                $isFirstCommissionForService = !ReferralCommission::query()
                    ->where('referral_code_id', $code->id)
                    ->where('service_id', $service->id)
                    ->lockForUpdate()
                    ->exists();

                if (!$code->canRewardPurchase($productId, $isFirstCommissionForService)) {
                    return;
                }

                $share = $code->revenueShareForProduct($productId);
                if ($share <= 0) {
                    return;
                }

                $commissionAmount = round($item->total() * ($share / 100), 2);

                if ($commissionAmount <= 0) {
                    return;
                }

                try {
                    $commission = ReferralCommission::create([
                        'referral_code_id' => $code->id,
                        'invoice_id' => $invoice->id,
                        'invoice_item_id' => $item->id,
                        'service_id' => $service->id,
                        'user_id' => $service->user_id,
                        'award_signature' => $awardSignature,
                        'currency_code' => $invoice->currency_code,
                        'amount' => $commissionAmount,
                        'status' => ReferralCommission::STATUS_AVAILABLE,
                        'meta' => [
                            'share' => $share,
                            'product_id' => $productId,
                            'service_plan_id' => $service->plan_id,
                        ],
                        'awarded_at' => now(),
                    ]);
                } catch (QueryException $exception) {
                    if (self::isUniqueConstraintViolation($exception)) {
                        return;
                    }

                    throw $exception;
                }

                $code->incrementPurchaseCounters($productId, $isFirstCommissionForService);
                $code->touch();

                return [
                    'code_id' => $code->id,
                    'commission_id' => $commission->id,
                ];
            });

            if ($result && $result['code_id'] && $result['commission_id']) {
                $code = ReferralCode::with('user')->find($result['code_id']);
                $commission = ReferralCommission::find($result['commission_id']);

                if ($code && $commission) {
                    ReferralNotifier::sendCommissionEarned($code, $commission);
                }
            }
        }
    }

    protected function resolveReferralCodeForService(Service $service): ?ReferralCode
    {
        if ($service->order_id) {
            $referralOrder = ReferralOrder::query()
                ->where('order_id', $service->order_id)
                ->latest('id')
                ->first();

            if ($referralOrder) {
                $code = ReferralCode::query()
                    ->with('packageOverrides')
                    ->find($referralOrder->referral_code_id);

                if ($code?->isActive()) {
                    return $code;
                }
            }
        }

        if ($service->coupon_id) {
            $code = ReferralCode::query()
                ->with('packageOverrides')
                ->where('coupon_id', $service->coupon_id)
                ->first();

            if ($code?->isActive()) {
                return $code;
            }
        }

        return null;
    }

    private static function awardSignature(int $invoiceItemId): string
    {
        return 'invoice-item:' . $invoiceItemId;
    }

    private static function invoiceItemAlreadyRewarded(int $invoiceItemId): bool
    {
        return ReferralCommission::query()
            ->where('invoice_item_id', $invoiceItemId)
            ->exists();
    }

    private static function isUniqueConstraintViolation(QueryException $exception): bool
    {
        return in_array($exception->getCode(), ['23000', '23505'], true);
    }
}
