<?php

namespace Paymenter\Extensions\Others\ReferralSystem\Services;

use App\Helpers\ExtensionHelper;
use Illuminate\Support\Str;
use Paymenter\Extensions\Others\ReferralSystem\Models\ReferralCode;
use Paymenter\Extensions\Others\ReferralSystem\Models\ReferralCommission;

class WithdrawalConfiguration
{
    public static function withdrawalHoldDays(): int
    {
        return max(0, (int) (ExtensionHelper::getExtension('other', 'ReferralSystem')->config('withdrawal_hold_days') ?? 0));
    }

    public static function configuredAllowedCurrencies(): array
    {
        $raw = (string) ExtensionHelper::getExtension('other', 'ReferralSystem')->config('withdrawal_allowed_currencies');

        return collect(preg_split('/[\s,]+/', $raw) ?: [])
            ->map(fn (string $currency) => strtoupper(trim($currency)))
            ->filter(fn (string $currency) => preg_match('/^[A-Z]{3}$/', $currency) === 1)
            ->unique()
            ->values()
            ->all();
    }

    public static function allowedCurrenciesForCode(ReferralCode $code): array
    {
        $holdDays = self::withdrawalHoldDays();
        $available = ReferralCommission::query()
            ->where('referral_code_id', $code->id)
            ->where('status', ReferralCommission::STATUS_AVAILABLE)
            ->when($holdDays > 0, fn ($query) => $query->where('awarded_at', '<=', now()->subDays($holdDays)))
            ->selectRaw('currency_code, SUM(amount) AS total')
            ->groupBy('currency_code')
            ->pluck('total', 'currency_code')
            ->reduce(function ($carry, $total, $currency) {
                $normalizedCurrency = strtoupper((string) $currency);
                if (preg_match('/^[A-Z]{3}$/', $normalizedCurrency) !== 1) {
                    return $carry;
                }

                $carry[$normalizedCurrency] = ($carry[$normalizedCurrency] ?? 0) + (float) $total;

                return $carry;
            }, collect());

        $configured = self::configuredAllowedCurrencies();
        if (!empty($configured)) {
            $configuredLookup = array_flip($configured);
            $available = $available->filter(fn ($_, string $currency) => isset($configuredLookup[$currency]));
        }

        return $available
            ->filter(fn (float $amount) => $amount > 0)
            ->sortKeys()
            ->all();
    }

    public static function currencyOptionsForCode(ReferralCode $code): array
    {
        return collect(self::allowedCurrenciesForCode($code))
            ->mapWithKeys(fn (float $amount, string $currency) => [
                $currency => sprintf(
                    '%s (%s %s)',
                    $currency,
                    __('referrals::referrals.balance_available'),
                    number_format($amount, 2)
                ),
            ])
            ->all();
    }

    public static function paymentMethods(): array
    {
        $raw = (string) ExtensionHelper::getExtension('other', 'ReferralSystem')->config('withdrawal_payment_methods');

        $entries = collect(preg_split('/[\r\n,]+/', $raw) ?: [])
            ->map(fn (string $entry) => trim($entry))
            ->filter();

        $methods = [];

        foreach ($entries as $entry) {
            $key = null;
            $label = null;

            if (str_contains($entry, ':')) {
                [$rawKey, $rawLabel] = array_map('trim', explode(':', $entry, 2));
                $key = self::normalizeMethodKey($rawKey);
                $label = trim($rawLabel);
            } else {
                $key = self::normalizeMethodKey($entry);
                $label = Str::of($entry)->replace('_', ' ')->replace('-', ' ')->title()->toString();
            }

            if (!$key) {
                continue;
            }

            $methods[$key] = $label !== '' ? $label : Str::headline($key);
        }

        if (!empty($methods)) {
            return $methods;
        }

        return [
            'bank_transfer' => 'Bank Transfer',
            'paypal' => 'PayPal',
            'crypto_wallet' => 'Crypto Wallet',
        ];
    }

    public static function paymentMethodLabel(?string $method): string
    {
        if (!$method) {
            return '—';
        }

        $methods = self::paymentMethods();

        if (isset($methods[$method])) {
            return $methods[$method];
        }

        return Str::of($method)->replace('_', ' ')->replace('-', ' ')->title()->toString();
    }

    private static function normalizeMethodKey(string $value): ?string
    {
        $normalized = Str::of($value)
            ->lower()
            ->replace([' ', '-'], '_')
            ->replaceMatches('/[^a-z0-9_]/', '')
            ->trim('_')
            ->toString();

        return $normalized !== '' ? $normalized : null;
    }
}
