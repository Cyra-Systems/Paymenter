<?php

namespace Paymenter\Extensions\Others\ReferralSystem\Admin\Resources\ReferralCodeResource\Pages;

use App\Helpers\ExtensionHelper;
use App\Models\Coupon;
use Filament\Resources\Pages\EditRecord;
use Paymenter\Extensions\Others\ReferralSystem\Admin\Resources\ReferralCodeResource;

class EditReferralCode extends EditRecord
{
    protected static string $resource = ReferralCodeResource::class;

    protected array $overrides = [];

    protected array $couponProducts = [];

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $this->record->loadMissing(['coupon.products', 'packageOverrides']);

        $extension = ExtensionHelper::getExtension('other', 'ReferralSystem');
        $defaultLimit = (int) ($extension->config('default_purchase_limit') ?? 0);

        return array_merge($data, [
            'coupon_type' => ReferralCodeResource::resolveCouponTypeOption($this->record),
            'coupon_value' => $this->record->coupon?->value ?? 0,
            'coupon_recurring' => $this->record->coupon?->recurring,
            'coupon_max_uses' => $this->record->coupon?->max_uses,
            'coupon_max_uses_per_user' => $this->record->coupon?->max_uses_per_user,
            'coupon_products' => $this->record->coupon?->products->pluck('id')->all() ?? [],
            'purchase_limit' => $data['purchase_limit'] ?? ($defaultLimit > 0 ? $defaultLimit : null),
            'overrides' => $this->record->packageOverrides
                ->map(fn ($override) => [
                    'product_id' => $override->product_id,
                    'revenue_share' => (float) $override->revenue_share,
                    'purchase_limit' => $override->purchase_limit,
                ])
                ->values()
                ->all(),
        ]);
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->overrides = $data['overrides'] ?? [];
        unset($data['overrides']);

        $this->couponProducts = $data['coupon_products'] ?? [];

        [$couponType, $couponRecurring] = ReferralCodeResource::normalizeCouponSelection(
            $data['coupon_type'],
            $data['coupon_recurring'] ?? null,
        );

        if (! $this->record->coupon instanceof Coupon) {
            $this->record->coupon()->associate(Coupon::create([
                'code' => $this->record->code,
                'type' => $couponType,
                'value' => $data['coupon_value'],
                'recurring' => $couponRecurring,
                'max_uses' => $data['coupon_max_uses'] ?? null,
                'max_uses_per_user' => $data['coupon_max_uses_per_user'] ?? null,
            ]));

            $this->record->save();
            $this->record->load('coupon');
        } else {
            $this->record->coupon->fill([
                'type' => $couponType,
                'value' => $data['coupon_value'],
                'recurring' => $couponRecurring,
                'max_uses' => $data['coupon_max_uses'] ?? null,
                'max_uses_per_user' => $data['coupon_max_uses_per_user'] ?? null,
            ])->save();
        }

        if ($this->couponProducts) {
            $this->record->coupon->products()->sync($this->couponProducts);
        } else {
            $this->record->coupon->products()->detach();
        }

        unset(
            $data['coupon_type'],
            $data['coupon_value'],
            $data['coupon_recurring'],
            $data['coupon_max_uses'],
            $data['coupon_max_uses_per_user'],
            $data['coupon_products']
        );

        return $data;
    }

    protected function afterSave(): void
    {
        $incoming = collect($this->overrides)
            ->filter(fn ($row) => !empty($row['product_id']))
            ->mapWithKeys(fn ($row) => [
                $row['product_id'] => [
                    'revenue_share' => $row['revenue_share'],
                    'purchase_limit' => $row['purchase_limit'] ?? null,
                ],
            ]);

        $existing = $this->record->packageOverrides()->get()->keyBy('product_id');

        // Update or create overrides
        foreach ($incoming as $productId => $payload) {
            $override = $existing->get($productId);

            if ($override) {
                $override->update($payload);
            } else {
                $this->record->packageOverrides()->create(array_merge($payload, ['product_id' => $productId]));
            }
        }

        // Delete removed overrides
        $toDelete = $existing->keys()->diff($incoming->keys());
        if ($toDelete->isNotEmpty()) {
            $this->record->packageOverrides()->whereIn('product_id', $toDelete->all())->delete();
        }

    }
}
