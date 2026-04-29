<?php

namespace Paymenter\Extensions\Others\ReferralSystem\Admin\Resources\ReferralCodeResource\Pages;

use App\Helpers\ExtensionHelper;
use App\Models\Coupon;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Paymenter\Extensions\Others\ReferralSystem\Admin\Resources\ReferralCodeResource;
use Paymenter\Extensions\Others\ReferralSystem\Models\ReferralCode;

class CreateReferralCode extends CreateRecord
{
    protected static string $resource = ReferralCodeResource::class;

    protected array $overrides = [];

    protected array $couponProducts = [];

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->overrides = $data['overrides'] ?? [];
        unset($data['overrides']);

        $this->couponProducts = $data['coupon_products'] ?? [];

        $normalizedCode = Str::lower($data['code']);
        $duplicateCoupon = Coupon::query()
            ->whereRaw('LOWER(code) = ?', [$normalizedCode])
            ->exists();
        $duplicateReferral = ReferralCode::query()
            ->whereRaw('LOWER(code) = ?', [$normalizedCode])
            ->exists();

        if ($duplicateCoupon || $duplicateReferral) {
            throw ValidationException::withMessages([
                'code' => __('referrals::referrals.code_taken'),
            ]);
        }

        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        return DB::transaction(function () use ($data) {
            [$couponType, $couponRecurring] = ReferralCodeResource::normalizeCouponSelection(
                $data['coupon_type'],
                $data['coupon_recurring'] ?? null,
            );

            $coupon = Coupon::create([
                'code' => $data['code'],
                'type' => $couponType,
                'value' => $data['coupon_value'],
                'recurring' => $couponRecurring,
                'max_uses' => $data['coupon_max_uses'] ?? null,
                'max_uses_per_user' => $data['coupon_max_uses_per_user'] ?? null,
            ]);

            if ($this->couponProducts) {
                $coupon->products()->sync($this->couponProducts);
            }

            $extension = ExtensionHelper::getExtension('other', 'ReferralSystem');
            $defaultLimit = (int) ($extension->config('default_purchase_limit') ?? 0);

            unset(
                $data['coupon_type'],
                $data['coupon_value'],
                $data['coupon_recurring'],
                $data['coupon_max_uses'],
                $data['coupon_max_uses_per_user'],
                $data['coupon_products']
            );

            $data['coupon_id'] = $coupon->id;
            $data['purchase_limit'] = ($data['purchase_limit'] ?? null) !== null && $data['purchase_limit'] !== ''
                ? (int) $data['purchase_limit']
                : ($defaultLimit > 0 ? $defaultLimit : null);

            /** @var ReferralCode $record */
            $record = static::getModel()::create($data);

            $overrides = collect($this->overrides)
                ->filter(fn ($row) => !empty($row['product_id']))
                ->map(fn ($row) => [
                    'product_id' => $row['product_id'],
                    'revenue_share' => $row['revenue_share'],
                    'purchase_limit' => $row['purchase_limit'] ?? null,
                ])->values();

            if ($overrides->isNotEmpty()) {
                $record->packageOverrides()->createMany($overrides->all());
            }

            return $record;
        });
    }
}
