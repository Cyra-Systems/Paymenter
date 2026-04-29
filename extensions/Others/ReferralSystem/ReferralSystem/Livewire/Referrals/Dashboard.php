<?php

namespace Paymenter\Extensions\Others\ReferralSystem\Livewire\Referrals;

use App\Helpers\ExtensionHelper;
use App\Livewire\Component;
use App\Models\Coupon;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Livewire\WithPagination;
use Paymenter\Extensions\Others\ReferralSystem\Models\ReferralApplication;
use Paymenter\Extensions\Others\ReferralSystem\Models\ReferralCode;
use Paymenter\Extensions\Others\ReferralSystem\Models\ReferralCommission;
use Paymenter\Extensions\Others\ReferralSystem\Models\ReferralWithdrawal;
use Paymenter\Extensions\Others\ReferralSystem\Services\WithdrawalConfiguration;

class Dashboard extends Component
{
    use WithPagination;

    public ?ReferralApplication $application = null;

    public ?ReferralCode $code = null;

    public array $codeOptions = [];

    public ?int $selectedCodeId = null;

    public ?int $selectedProductId = null;

    public array $productOptions = [];

    public string $message = '';

    public string $requested_code = '';

    public string $desired_revenue_share = '';

    public string $withdrawal_currency = '';

    public string $withdrawal_amount = '';

    public string $withdrawal_notes = '';

    public string $withdrawal_payment_method = '';

    public string $withdrawal_payment_method_info = '';

    public string $requirementsCopy = '';

    public bool $allowCustomCodes = false;

    public float $minWithdrawalAmount = 0;

    public int $withdrawalHoldDays = 0;

    public int $applicationCooldownDays = 0;

    public array $withdrawalCurrencyOptions = [];

    public array $withdrawalCurrencyBalances = [];

    public array $withdrawalMethodOptions = [];

    public function mount(): void
    {
        $extension = ExtensionHelper::getExtension('other', 'ReferralSystem');
        $this->requirementsCopy = (string) $extension->config('application_requirements');
        $this->allowCustomCodes = (bool) $extension->config('allow_custom_code_requests');
        $this->minWithdrawalAmount = max(1, (float) ($extension->config('min_withdrawal_amount') ?? 1));
        $this->withdrawalHoldDays = WithdrawalConfiguration::withdrawalHoldDays();
        $this->applicationCooldownDays = (int) ($extension->config('application_cooldown_days') ?? 0);
        $this->loadProducts();
        $this->refreshState();
    }

    public function loadProducts(): void
    {
        $this->productOptions = Product::query()
            ->with('category')
            ->where('hidden', false)
            ->get()
            ->mapWithKeys(fn ($product) => [
                $product->id => $product->name . ' (' . ($product->category->name ?? 'Uncategorized') . ')'
            ])
            ->toArray();
    }

    public function getReferralUrl(): string
    {
        if (!$this->code) {
            return url('/');
        }

        $baseUrl = url('/');

        if ($this->selectedProductId) {
            $product = Product::with('category')->find($this->selectedProductId);
            if ($product && $product->category) {
                $baseUrl = url('/products/' . $product->category->slug . '/' . $product->slug);
            }
        }

        return $baseUrl . '?ref=' . $this->code->code;
    }

    public function copyLink(): void
    {
        $this->dispatch('copy-to-clipboard', value: $this->getReferralUrl());
        $this->notify(__('referrals::referrals.link_copied'));
    }

    public function copyCode(): void
    {
        if (!$this->code) {
            return;
        }

        $this->dispatch('copy-to-clipboard', value: $this->code->code);
        $this->notify(__('referrals::referrals.link_copied'));
    }

    public function refreshState(): void
    {
        $user = Auth::user();
        $codes = $user->referralCodes()
            ->with('coupon')
            ->latest()
            ->get();
        $this->codeOptions = $codes->pluck('code', 'id')->toArray();

        if ($codes->isEmpty()) {
            $this->code = null;
            $this->selectedCodeId = null;
        } else {
            if ($this->selectedCodeId) {
                $this->code = $codes->firstWhere('id', $this->selectedCodeId);
            }

            if (!$this->code) {
                $this->code = $codes->first();
                $this->selectedCodeId = $this->code?->id;
            }
        }

        $this->application = $user->referralApplications()->latest()->first();
        $this->refreshWithdrawalOptions();
    }

    public function updatedSelectedCodeId($value): void
    {
        $codeId = (int) $value;

        $code = ReferralCode::with('coupon')
            ->whereKey($codeId)
            ->where('user_id', Auth::id())
            ->first();

        $this->code = $code;

        if (!$this->code && !empty($this->codeOptions)) {
            $fallbackId = array_key_first($this->codeOptions);
            $this->code = $fallbackId
                ? ReferralCode::with('coupon')
                    ->whereKey($fallbackId)
                    ->where('user_id', Auth::id())
                    ->first()
                : null;
            $this->selectedCodeId = $this->code?->id;
        }

        if (!$this->code) {
            $this->selectedCodeId = null;
        }

        $this->refreshWithdrawalOptions();
        $this->resetPage();
    }

    public function render()
    {
        $hasAnyAvailableCommissions = $this->hasAnyAvailableCommissions();

        return view('referrals::account.dashboard', [
            'commissionTotals' => $this->commissionTotals(),
            'commissions' => $this->code
                ? $this->code->commissions()->whereNull('meta->split_from')->latest('awarded_at')->paginate(config('settings.pagination'))
                : collect(),
            'withdrawals' => $this->code
                ? $this->code->withdrawals()->latest()->limit(10)->get()
                : collect(),
            'canRequestWithdrawal' => $this->canRequestWithdrawal(),
            'canApply' => $this->canApply(),
            'codeOptions' => $this->codeOptions,
            'productOptions' => $this->productOptions,
            'referralUrl' => $this->getReferralUrl(),
            'minWithdrawalAmount' => $this->minWithdrawalAmount,
            'withdrawalHoldDays' => $this->withdrawalHoldDays,
            'withdrawalCurrencyOptions' => $this->withdrawalCurrencyOptions,
            'withdrawalMethodOptions' => $this->withdrawalMethodOptions,
            'selectedCurrencyAvailableBalance' => $this->selectedCurrencyAvailableBalance(),
            'availableBalanceWidget' => $this->availableBalanceWidgetData(),
            'balanceWidget' => $this->balanceWidgetData(),
            'showNoAllowedCurrencyWarning' => $this->showNoAllowedCurrencyWarning($hasAnyAvailableCommissions),
            'showNoPaymentMethodsWarning' => $this->showNoPaymentMethodsWarning($hasAnyAvailableCommissions),
        ])->layoutData([
            'sidebar' => true,
            'title' => __('referrals::referrals.title'),
        ]);
    }

    public function submitApplication(): void
    {
        $data = $this->validate([
            'message' => ['required', 'string', 'min:20', 'max:2000'],
            'requested_code' => [
                Rule::requiredIf($this->allowCustomCodes),
                'nullable',
                'alpha_dash:ascii',
                'min:4',
                'max:32',
            ],
            'desired_revenue_share' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        $requestedCode = $this->allowCustomCodes ? trim((string) ($data['requested_code'] ?? '')) : '';
        $result = DB::transaction(function () use ($data, $requestedCode) {
            $userId = (int) Auth::id();

            User::query()
                ->whereKey($userId)
                ->lockForUpdate()
                ->first();

            if (ReferralCode::query()->where('user_id', $userId)->exists()) {
                return ['status' => 'already_has_code'];
            }

            $latestApplication = ReferralApplication::query()
                ->where('user_id', $userId)
                ->latest('id')
                ->lockForUpdate()
                ->first();

            if ($latestApplication?->status === ReferralApplication::STATUS_PENDING) {
                return ['status' => 'application_pending'];
            }

            if (
                $latestApplication?->status === ReferralApplication::STATUS_REJECTED
                && $this->applicationCooldownDays > 0
                && $latestApplication->decision_at
            ) {
                $cooldownEndsAt = $latestApplication->decision_at->copy()->addDays($this->applicationCooldownDays);
                if ($cooldownEndsAt->isFuture()) {
                    return [
                        'status' => 'cooldown_active',
                        'days' => max(1, (int) ceil(now()->diffInDays($cooldownEndsAt, false))),
                    ];
                }
            }

            if ($requestedCode !== '') {
                $normalizedCode = strtolower($requestedCode);
                $codeExists = ReferralCode::query()
                    ->whereRaw('LOWER(code) = ?', [$normalizedCode])
                    ->exists();
                $couponExists = Coupon::query()
                    ->whereRaw('LOWER(code) = ?', [$normalizedCode])
                    ->exists();

                if ($codeExists || $couponExists) {
                    return ['status' => 'code_taken'];
                }
            }

            $application = ReferralApplication::create([
                'user_id' => $userId,
                'status' => ReferralApplication::STATUS_PENDING,
                'requested_code' => $requestedCode !== '' ? $requestedCode : null,
                'message' => $data['message'],
                'desired_revenue_share' => $data['desired_revenue_share'] ?? null,
            ]);

            return [
                'status' => 'created',
                'application_id' => $application->id,
            ];
        });

        if ($result['status'] === 'already_has_code') {
            $this->refreshState();
            $this->notify(__('referrals::referrals.already_has_code'), 'error');

            return;
        }

        if ($result['status'] === 'application_pending') {
            $this->refreshState();
            $this->notify(__('referrals::referrals.application_pending'), 'info');

            return;
        }

        if ($result['status'] === 'cooldown_active') {
            $this->refreshState();
            $this->notify(__('referrals::referrals.cooldown_active', ['days' => $result['days']]), 'error');

            return;
        }

        if ($result['status'] === 'code_taken') {
            $this->addError('requested_code', __('referrals::referrals.code_taken'));

            return;
        }

        $this->application = ReferralApplication::query()->find($result['application_id']);
        $this->reset([
            'message',
            'requested_code',
            'desired_revenue_share',
        ]);

        $this->notify(__('referrals::referrals.application_submitted'));
    }

    public function submitWithdrawal(): void
    {
        $this->refreshState();

        if (!$this->code) {
            $this->notify(__('referrals::referrals.no_code_yet'), 'error');

            return;
        }

        if ($this->code->isSuspended()) {
            $this->notify(__('referrals::referrals.code_suspended'), 'error');

            return;
        }

        if (empty($this->withdrawalCurrencyOptions)) {
            $this->notify(__('referrals::referrals.withdrawal_no_allowed_currency'), 'error');

            return;
        }

        if (empty($this->withdrawalMethodOptions)) {
            $this->notify(__('referrals::referrals.withdrawal_no_payment_methods'), 'error');

            return;
        }

        $minAmount = max(1, $this->minWithdrawalAmount);
        $allowedCurrencies = array_keys($this->withdrawalCurrencyOptions);
        $allowedPaymentMethods = array_keys($this->withdrawalMethodOptions);

        $data = $this->validate([
            'withdrawal_currency' => ['required', 'string', 'size:3', Rule::in($allowedCurrencies)],
            'withdrawal_amount' => ['required', 'numeric', 'decimal:0,2', 'min:' . $minAmount],
            'withdrawal_payment_method' => ['required', 'string', 'max:64', Rule::in($allowedPaymentMethods)],
            'withdrawal_payment_method_info' => ['required', 'string', 'min:6', 'max:2000'],
            'withdrawal_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $currency = strtoupper($data['withdrawal_currency']);
        $amount = round((float) $data['withdrawal_amount'], 2);
        $paymentMethod = (string) $data['withdrawal_payment_method'];
        $paymentMethodInfo = trim((string) $data['withdrawal_payment_method_info']);
        $selectedCodeId = (int) ($this->code->id ?? 0);

        $result = DB::transaction(function () use ($currency, $amount, $data, $paymentMethod, $paymentMethodInfo, $selectedCodeId) {
            $code = ReferralCode::query()
                ->whereKey($selectedCodeId)
                ->where('user_id', Auth::id())
                ->lockForUpdate()
                ->first();

            if (!$code || !$code->isActive()) {
                return ['status' => 'code_unavailable'];
            }

            $commissions = $code->commissions()
                ->where('status', ReferralCommission::STATUS_AVAILABLE)
                ->where('currency_code', $currency)
                ->when($this->withdrawalHoldDays > 0, fn ($query) => $query->where('awarded_at', '<=', now()->subDays($this->withdrawalHoldDays)))
                ->orderBy('awarded_at')
                ->lockForUpdate()
                ->get();

            $totalAvailable = round((float) $commissions->sum('amount'), 2);

            if (($totalAvailable + 0.00001) < $amount) {
                return ['status' => 'insufficient_balance'];
            }

            /** @var ReferralWithdrawal $withdrawal */
            $withdrawal = $code->withdrawals()->create([
                'user_id' => Auth::id(),
                'amount' => $amount,
                'currency_code' => $currency,
                'payment_method' => $paymentMethod,
                'payment_method_info' => $paymentMethodInfo,
                'status' => ReferralWithdrawal::STATUS_PENDING,
                'notes' => $data['withdrawal_notes'] ?? null,
            ]);

            $remaining = $amount;

            foreach ($commissions as $commission) {
                if ($remaining <= 0) {
                    break;
                }

                $commissionAmount = round((float) $commission->amount, 2);

                if ($commissionAmount <= ($remaining + 0.00001)) {
                    $commission->reserveForWithdrawal($withdrawal->id);
                    $remaining = round(max(0, $remaining - $commissionAmount), 2);

                    continue;
                }

                // Split the commission so the remainder stays available.
                $splitAmount = round($remaining, 2);
                $balance = round($commissionAmount - $splitAmount, 2);
                $originalMeta = $commission->meta ?? [];

                $commission->forceFill([
                    'amount' => $splitAmount,
                    'meta' => array_merge($originalMeta, ['reserved_amount' => $splitAmount]),
                ])->save();
                $commission->reserveForWithdrawal($withdrawal->id);

                if ($balance > 0) {
                    ReferralCommission::create([
                        'referral_code_id' => $commission->referral_code_id,
                        'invoice_id' => $commission->invoice_id,
                        'invoice_item_id' => $commission->invoice_item_id,
                        'service_id' => $commission->service_id,
                        'user_id' => $commission->user_id,
                        'currency_code' => $commission->currency_code,
                        'amount' => $balance,
                        'status' => ReferralCommission::STATUS_AVAILABLE,
                        'meta' => array_merge($originalMeta, [
                            'split_from' => $commission->id,
                        ]),
                        'awarded_at' => $commission->awarded_at,
                    ]);
                }

                $remaining = 0;
            }

            return ['status' => 'created'];
        });

        if ($result['status'] === 'code_unavailable') {
            $this->refreshState();
            $this->notify(
                $this->code?->isSuspended()
                    ? __('referrals::referrals.code_suspended')
                    : __('referrals::referrals.no_code_yet'),
                'error'
            );

            return;
        }

        if ($result['status'] === 'insufficient_balance') {
            $this->refreshState();
            $this->addError('withdrawal_amount', __('referrals::referrals.insufficient_balance'));

            return;
        }

        $this->notify(__('referrals::referrals.withdrawal_requested'));

        $this->reset([
            'withdrawal_currency',
            'withdrawal_amount',
            'withdrawal_payment_method_info',
            'withdrawal_notes',
        ]);

        $this->refreshState();
        $this->resetPage();
    }

    private function commissionTotals(): Collection
    {
        if (!$this->code) {
            return collect();
        }

        $rows = $this->code->commissions()
            ->selectRaw('currency_code, status, SUM(amount) AS total')
            ->groupBy('currency_code', 'status')
            ->get();

        return $rows->groupBy('currency_code')->map(function ($group) {
            $map = [
                'available' => 0.0,
                'reserved' => 0.0,
                'paid' => 0.0,
            ];

            foreach ($group as $row) {
                $status = $row->status;
                if ($status === ReferralCommission::STATUS_AVAILABLE) {
                    $map['available'] = (float) $row->total;
                } elseif ($status === ReferralCommission::STATUS_RESERVED) {
                    $map['reserved'] = (float) $row->total;
                } elseif ($status === ReferralCommission::STATUS_PAID) {
                    $map['paid'] = (float) $row->total;
                }
            }

            $map['total'] = $map['available'] + $map['reserved'] + $map['paid'];

            return $map;
        });
    }

    private function canRequestWithdrawal(): bool
    {
        if (!$this->code) {
            return false;
        }

        // Cannot request withdrawal if code is suspended
        if ($this->code->isSuspended()) {
            return false;
        }

        return !empty($this->withdrawalCurrencyOptions) && !empty($this->withdrawalMethodOptions);
    }

    private function hasAnyAvailableCommissions(): bool
    {
        if (!$this->code) {
            return false;
        }

        return $this->code->commissions()
            ->where('status', ReferralCommission::STATUS_AVAILABLE)
            ->exists();
    }

    private function showNoAllowedCurrencyWarning(bool $hasAnyAvailableCommissions): bool
    {
        if (!$this->code || $this->code->isSuspended()) {
            return false;
        }

        return empty($this->withdrawalCurrencyOptions) && $hasAnyAvailableCommissions;
    }

    private function showNoPaymentMethodsWarning(bool $hasAnyAvailableCommissions): bool
    {
        if (!$this->code || $this->code->isSuspended()) {
            return false;
        }

        return empty($this->withdrawalMethodOptions) && $hasAnyAvailableCommissions;
    }

    public function canApply(): bool
    {
        // Has active code already
        if (!empty($this->codeOptions)) {
            return false;
        }

        // Application is pending
        if ($this->application && $this->application->status === ReferralApplication::STATUS_PENDING) {
            return false;
        }

        // Check cooldown after rejection
        if ($this->application
            && $this->application->status === ReferralApplication::STATUS_REJECTED
            && $this->applicationCooldownDays > 0
            && $this->application->decision_at
            && $this->application->decision_at->copy()->addDays($this->applicationCooldownDays)->isFuture()
        ) {
            return false;
        }

        return true;
    }

    private function refreshWithdrawalOptions(): void
    {
        $this->withdrawalMethodOptions = WithdrawalConfiguration::paymentMethods();

        if (!$this->code) {
            $this->withdrawalCurrencyOptions = [];
            $this->withdrawalCurrencyBalances = [];
            $this->withdrawal_currency = '';
            $this->withdrawal_payment_method = '';

            return;
        }

        $this->withdrawalCurrencyBalances = WithdrawalConfiguration::allowedCurrenciesForCode($this->code);
        $this->withdrawalCurrencyOptions = WithdrawalConfiguration::currencyOptionsForCode($this->code);

        if ($this->withdrawal_currency === '' || !array_key_exists($this->withdrawal_currency, $this->withdrawalCurrencyOptions)) {
            $this->withdrawal_currency = array_key_first($this->withdrawalCurrencyOptions) ?? '';
        }

        if ($this->withdrawal_payment_method === '' || !array_key_exists($this->withdrawal_payment_method, $this->withdrawalMethodOptions)) {
            $this->withdrawal_payment_method = array_key_first($this->withdrawalMethodOptions) ?? '';
        }
    }

    private function selectedCurrencyAvailableBalance(): float
    {
        if ($this->withdrawal_currency === '') {
            return 0;
        }

        return (float) ($this->withdrawalCurrencyBalances[$this->withdrawal_currency] ?? 0);
    }

    private function balanceWidgetData(): array
    {
        if (empty($this->withdrawalCurrencyBalances)) {
            return [
                'value' => '0.00',
                'hint' => __('referrals::referrals.balance_widget_empty'),
            ];
        }

        if (count($this->withdrawalCurrencyBalances) === 1) {
            $currency = (string) array_key_first($this->withdrawalCurrencyBalances);
            $amount = (float) ($this->withdrawalCurrencyBalances[$currency] ?? 0);

            return [
                'value' => number_format($amount, 2) . ' ' . $currency,
                'hint' => __('referrals::referrals.balance_widget_single_hint'),
            ];
        }

        $preview = collect($this->withdrawalCurrencyBalances)
            ->take(3)
            ->map(fn (float $amount, string $currency) => $currency . ' ' . number_format($amount, 2))
            ->implode(' • ');

        return [
            'value' => __('referrals::referrals.balance_widget_multi_value', ['count' => count($this->withdrawalCurrencyBalances)]),
            'hint' => $preview,
        ];
    }

    private function availableBalanceWidgetData(): array
    {
        $balances = $this->commissionTotals()
            ->mapWithKeys(fn (array $totals, string $currency) => [$currency => round((float) ($totals['available'] ?? 0), 2)])
            ->filter(fn (float $amount) => $amount > 0);

        if ($balances->isEmpty()) {
            return [
                'value' => '0.00',
                'hint' => __('referrals::referrals.available_balance_widget_empty'),
            ];
        }

        if ($balances->count() === 1) {
            $currency = (string) $balances->keys()->first();
            $amount = (float) $balances->first();

            return [
                'value' => number_format($amount, 2) . ' ' . $currency,
                'hint' => __('referrals::referrals.available_balance_widget_single_hint'),
            ];
        }

        $preview = $balances
            ->take(3)
            ->map(fn (float $amount, string $currency) => $currency . ' ' . number_format($amount, 2))
            ->implode(' • ');

        return [
            'value' => __('referrals::referrals.available_balance_widget_multi_value', ['count' => $balances->count()]),
            'hint' => $preview,
        ];
    }
}
