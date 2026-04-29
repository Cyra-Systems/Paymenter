<div class="container mt-14">
    <x-navigation.breadcrumb />

    <div class="px-2 space-y-6">
        @if ($code)
            @if ($code->isSuspended())
                <div class="rounded-lg border border-red-400/40 bg-red-500/10 p-4 text-sm text-red-600 dark:text-red-200">
                    <p class="font-semibold">{{ __('referrals::referrals.code_suspended') }}</p>
                    @if ($code->suspended_at)
                        <p class="mt-1 text-xs">{{ __('referrals::referrals.suspended_since', ['date' => $code->suspended_at->format('Y-m-d')]) }}</p>
                    @endif
                </div>
            @endif
            @if (count($codeOptions) > 1)
                <div class="rounded-lg bg-background-secondary border border-neutral p-6 shadow">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('referrals::referrals.manage_codes_title') }}</h3>
                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">{{ __('referrals::referrals.manage_codes_help') }}</p>
                    <div class="mt-4 max-w-xs">
                        <label for="selected_code" class="block text-sm font-medium text-gray-600 dark:text-gray-300">{{ __('referrals::referrals.manage_codes_label') }}</label>
                        <select id="selected_code" wire:model="selectedCodeId" class="mt-2 w-full rounded border border-neutral bg-background-secondary dark:bg-black/30 px-4 py-2 text-sm text-gray-900 dark:text-white focus:border-primary-400 focus:outline-none focus:ring-0">
                            @foreach ($codeOptions as $codeId => $codeLabel)
                                <option value="{{ $codeId }}">{{ $codeLabel }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            @endif

            <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-6">
                <div class="rounded-sm border border-neutral bg-background-secondary p-4">
                    <p class="text-sm uppercase tracking-wide text-gray-600 dark:text-gray-400">{{ __('referrals::referrals.total_clicks') }}</p>
                    <p class="mt-2 text-2xl font-semibold text-gray-900 dark:text-white">{{ number_format($code->clicks_count ?? 0) }}</p>
                </div>
                <div class="rounded-sm border border-neutral bg-background-secondary p-4">
                    <p class="text-sm uppercase tracking-wide text-gray-600 dark:text-gray-400">{{ __('referrals::referrals.total_purchases') }}</p>
                    <p class="mt-2 text-2xl font-semibold text-gray-900 dark:text-white">{{ number_format($code->purchases_count ?? 0) }}</p>
                    @if ($code->remaining_purchases !== null)
                        <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('referrals::referrals.remaining_purchases', ['count' => number_format($code->remaining_purchases ?? 0)]) }}</p>
                    @endif
                </div>
                <div class="rounded-sm border border-neutral bg-background-secondary p-4">
                    <p class="text-sm uppercase tracking-wide text-gray-600 dark:text-gray-400">{{ __('referrals::referrals.revenue_share_default') }}</p>
                    <p class="mt-2 text-2xl font-semibold text-gray-900 dark:text-white">{{ $code->default_revenue_share }}%</p>
                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('referrals::referrals.revenue_share_hint') }}</p>
                </div>
                <div class="rounded-sm border border-neutral bg-background-secondary p-4">
                    <p class="text-sm uppercase tracking-wide text-gray-600 dark:text-gray-400">{{ __('referrals::referrals.customer_discount') }}</p>
                    <p class="mt-2 text-2xl font-semibold text-gray-900 dark:text-white">
                        {{ $code->discountLabel() ?? __('referrals::referrals.customer_discount_none') }}
                    </p>
                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('referrals::referrals.customer_discount_hint') }}</p>
                </div>
                <div class="rounded-sm border border-neutral bg-background-secondary p-4">
                    <p class="text-sm uppercase tracking-wide text-gray-600 dark:text-gray-400">{{ __('referrals::referrals.available_balance_widget_title') }}</p>
                    <p class="mt-2 text-2xl font-semibold text-gray-900 dark:text-white">{{ $availableBalanceWidget['value'] }}</p>
                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ $availableBalanceWidget['hint'] }}</p>
                </div>
                <div class="rounded-sm border border-neutral bg-background-secondary p-4">
                    <p class="text-sm uppercase tracking-wide text-gray-600 dark:text-gray-400">{{ __('referrals::referrals.balance_widget_title') }}</p>
                    <p class="mt-2 text-2xl font-semibold text-gray-900 dark:text-white">{{ $balanceWidget['value'] }}</p>
                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ $balanceWidget['hint'] }}</p>
                </div>
            </div>

            <div class="rounded-lg mt-2 bg-background-secondary border border-neutral p-6 shadow">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('referrals::referrals.share_code_title') }}</h2>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">{{ __('referrals::referrals.share_code_help') }}</p>
                
                {{-- Referral Code Display --}}
                <div class="mt-4">
                    <label class="block text-sm font-medium text-gray-600 dark:text-gray-300 mb-2">{{ __('referrals::referrals.your_code') }}</label>
                    <div class="flex gap-2 items-center max-w-md">
                        <input type="text" id="referral-code" value="{{ $code->code }}" readonly class="w-full rounded border border-neutral bg-background-secondary dark:bg-black/30 px-4 py-2 text-sm text-gray-900 dark:text-white" />
                        <button type="button" wire:click="copyCode" class="px-4 py-2 rounded border border-neutral bg-background-secondary hover:bg-neutral/20 text-sm text-gray-600 dark:text-gray-300 whitespace-nowrap">
                            {{ __('referrals::referrals.copy') }}
                        </button>
                    </div>
                </div>

                {{-- Product Selector --}}
                @if (count($productOptions) > 0)
                    <div class="mt-4 max-w-md">
                        <label for="selected_product" class="block text-sm font-medium text-gray-600 dark:text-gray-300 mb-2">{{ __('referrals::referrals.link_to_product') }}</label>
                        <select id="selected_product" wire:model.live="selectedProductId" class="w-full rounded border border-neutral bg-background-secondary dark:bg-black/30 px-4 py-2 text-sm text-gray-900 dark:text-white focus:border-primary-400 focus:outline-none focus:ring-0">
                            <option value="">{{ __('referrals::referrals.homepage') }}</option>
                            @foreach ($productOptions as $productId => $productLabel)
                                <option value="{{ $productId }}">{{ $productLabel }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif

                {{-- Referral Link Display --}}
                <div class="mt-4">
                    <label class="block text-sm font-medium text-gray-600 dark:text-gray-300 mb-2">{{ __('referrals::referrals.your_referral_link') }}</label>
                    <div class="flex gap-2 items-center">
                        <input type="text" id="referral-link" value="{{ $referralUrl }}" readonly class="w-full rounded border border-neutral bg-background-secondary dark:bg-black/30 px-4 py-2 text-sm text-gray-900 dark:text-white" />
                        <button type="button" wire:click="copyLink" class="px-4 py-2 rounded border border-neutral bg-background-secondary hover:bg-neutral/20 text-sm text-gray-600 dark:text-gray-300 whitespace-nowrap">
                            {{ __('referrals::referrals.copy') }}
                        </button>
                    </div>
                </div>
            </div>

            <script>
                if (!window.__referralCopyListenerBound) {
                    window.__referralCopyListenerBound = true;

                    window.addEventListener('copy-to-clipboard', async (event) => {
                        const value = event?.detail?.value;
                        if (!value) return;

                        if (navigator.clipboard?.writeText) {
                            try {
                                await navigator.clipboard.writeText(value);
                                return;
                            } catch (_) {
                                // Fallback below.
                            }
                        }

                        const area = document.createElement('textarea');
                        area.value = value;
                        area.style.position = 'fixed';
                        area.style.top = '-9999px';
                        document.body.appendChild(area);
                        area.focus();
                        area.select();
                        document.execCommand('copy');
                        document.body.removeChild(area);
                    });
                }
            </script>

            <div class="rounded-lg mt-2 bg-background-secondary border border-neutral p-6 shadow">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('referrals::referrals.balance_overview') }}</h3>
                @php
                    $balanceOverviewColumnCount = max(1, min(6, $commissionTotals->count()));
                @endphp
                <div @class([
                    'mt-4 grid gap-6',
                    'md:grid-cols-1' => $balanceOverviewColumnCount === 1,
                    'md:grid-cols-2' => $balanceOverviewColumnCount === 2,
                    'md:grid-cols-3' => $balanceOverviewColumnCount === 3,
                    'md:grid-cols-4' => $balanceOverviewColumnCount === 4,
                    'md:grid-cols-5' => $balanceOverviewColumnCount === 5,
                    'md:grid-cols-6' => $balanceOverviewColumnCount === 6,
                ])>
                    @forelse ($commissionTotals as $currency => $totals)
                        <div>
                            <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ $currency }}</p>
                            <dl class="mt-2 space-y-2 text-sm text-gray-600 dark:text-gray-300">
                                <div class="flex justify-between">
                                    <dt>{{ __('referrals::referrals.balance_total') }}</dt>
                                    <dd>{{ number_format($totals['total'] ?? 0, 2) }}</dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt>{{ __('referrals::referrals.balance_available') }}</dt>
                                    <dd>{{ number_format($totals['available'] ?? 0, 2) }}</dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt>{{ __('referrals::referrals.balance_reserved') }}</dt>
                                    <dd>{{ number_format($totals['reserved'] ?? 0, 2) }}</dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt>{{ __('referrals::referrals.balance_paid') }}</dt>
                                    <dd>{{ number_format($totals['paid'] ?? 0, 2) }}</dd>
                                </div>
                            </dl>
                        </div>
                    @empty
                        <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('referrals::referrals.no_commissions_yet') }}</p>
                    @endforelse
                </div>
            </div>

            @if ($canRequestWithdrawal)
                <div class="rounded-lg mt-2 bg-background-secondary border border-neutral p-6 shadow">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('referrals::referrals.request_withdrawal') }}</h3>
                    @if ($minWithdrawalAmount > 0)
                        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">{{ __('referrals::referrals.min_withdrawal_hint', ['amount' => number_format($minWithdrawalAmount, 2)]) }}</p>
                    @endif
                    @if ($withdrawalHoldDays > 0)
                        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">{{ __('referrals::referrals.withdrawal_hold_hint', ['days' => $withdrawalHoldDays]) }}</p>
                    @endif
                    <form wire:submit.prevent="submitWithdrawal" class="mt-4 space-y-4">
                        <div class="grid gap-6 md:grid-cols-2">
                            <x-form.select name="withdrawal_currency" label="{{ __('referrals::referrals.currency') }}" wire:model.live="withdrawal_currency" required>
                                @foreach ($withdrawalCurrencyOptions as $currencyCode => $currencyLabel)
                                    <option value="{{ $currencyCode }}">{{ $currencyLabel }}</option>
                                @endforeach
                            </x-form.select>
                            <x-form.input name="withdrawal_amount" label="{{ __('referrals::referrals.amount') }}" type="number" step="0.01" min="{{ max(1, $minWithdrawalAmount) }}" max="{{ $selectedCurrencyAvailableBalance > 0 ? $selectedCurrencyAvailableBalance : '' }}" wire:model.defer="withdrawal_amount" />
                        </div>
                        @if ($withdrawal_currency)
                            <p class="-mt-2 text-xs text-gray-600 dark:text-gray-400">
                                {{ __('referrals::referrals.withdrawal_available_hint', ['amount' => number_format($selectedCurrencyAvailableBalance, 2), 'currency' => $withdrawal_currency]) }}
                            </p>
                        @endif
                        <div class="grid gap-6 md:grid-cols-2">
                            <x-form.select name="withdrawal_payment_method" label="{{ __('referrals::referrals.withdrawal_payment_method') }}" wire:model.defer="withdrawal_payment_method" required>
                                @foreach ($withdrawalMethodOptions as $methodKey => $methodLabel)
                                    <option value="{{ $methodKey }}">{{ $methodLabel }}</option>
                                @endforeach
                            </x-form.select>
                            <x-form.input name="withdrawal_notes" label="{{ __('referrals::referrals.withdrawal_notes') }}" wire:model.defer="withdrawal_notes" />
                        </div>
                        <x-form.textarea name="withdrawal_payment_method_info" label="{{ __('referrals::referrals.withdrawal_payment_method_info') }}" wire:model.defer="withdrawal_payment_method_info" rows="4" required />
                        <x-button.primary type="submit" class="!w-fit">
                            {{ __('referrals::referrals.submit_withdrawal') }}
                        </x-button.primary>
                    </form>
                </div>
            @elseif ($showNoAllowedCurrencyWarning)
                <div class="rounded-lg mt-2 border border-yellow-400/40 bg-yellow-500/10 p-4 text-sm text-yellow-700 dark:text-yellow-200">
                    {{ __('referrals::referrals.withdrawal_no_allowed_currency') }}
                </div>
            @elseif ($showNoPaymentMethodsWarning)
                <div class="rounded-lg mt-2 border border-yellow-400/40 bg-yellow-500/10 p-4 text-sm text-yellow-700 dark:text-yellow-200">
                    {{ __('referrals::referrals.withdrawal_no_payment_methods') }}
                </div>
            @endif

            <div class="rounded-lg mt-2 bg-background-secondary border border-neutral p-6 shadow">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('referrals::referrals.recent_commissions') }}</h3>
                <div class="mt-4 overflow-x-auto">
                    <table class="min-w-full w-full table-fixed divide-y divide-gray-200 dark:divide-white/10 text-left text-sm text-gray-700 dark:text-gray-200">
                        <thead>
                            <tr>
                                <th class="px-4 py-2">{{ __('referrals::referrals.table_awarded') }}</th>
                                <th class="px-4 py-2">{{ __('referrals::referrals.table_amount') }}</th>
                                <th class="px-4 py-2">{{ __('referrals::referrals.table_currency') }}</th>
                                <th class="px-4 py-2">{{ __('referrals::referrals.table_status') }}</th>
                                <th class="px-4 py-2">{{ __('referrals::referrals.table_order') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-white/5">
                            @forelse ($commissions as $commission)
                                <tr>
                                    <td class="px-4 py-2">{{ optional($commission->awarded_at)->format('Y-m-d H:i') ?? '—' }}</td>
                                    <td class="px-4 py-2">{{ number_format($commission->amount ?? 0, 2) }}</td>
                                    <td class="px-4 py-2">{{ $commission->currency_code }}</td>
                                    <td class="px-4 py-2">
                                        <span class="rounded bg-white/10 px-2 py-2 text-xs uppercase tracking-wide">
                                            {{ __('referrals::referrals.status_' . $commission->status) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-2">
                                        @if ($commission->invoice_id)
                                            #{{ $commission->invoice_id }}
                                        @else
                                            —
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td class="px-4 py-4 text-center text-sm text-gray-600 dark:text-gray-400" colspan="5">
                                        {{ __('referrals::referrals.no_commissions_yet') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($commissions instanceof \Illuminate\Contracts\Pagination\Paginator)
                    <div class="mt-4">
                        {{ $commissions->links() }}
                    </div>
                @endif
            </div>

            <div class="rounded-lg mt-2 bg-background-secondary border border-neutral p-6 shadow">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('referrals::referrals.recent_withdrawals') }}</h3>
                <div class="mt-4 overflow-x-auto">
                    <table class="min-w-full w-full table-fixed divide-y divide-gray-200 dark:divide-white/10 text-left text-sm text-gray-700 dark:text-gray-200">
                        <thead>
                            <tr>
                                <th class="px-4 py-2">{{ __('referrals::referrals.table_requested') }}</th>
                                <th class="px-4 py-2">{{ __('referrals::referrals.table_amount') }}</th>
                                <th class="px-4 py-2">{{ __('referrals::referrals.table_currency') }}</th>
                                <th class="px-4 py-2">{{ __('referrals::referrals.withdrawal_payment_method') }}</th>
                                <th class="px-4 py-2">{{ __('referrals::referrals.table_status') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-white/5">
                            @forelse ($withdrawals as $withdrawal)
                                <tr>
                                    <td class="px-4 py-2">{{ $withdrawal->created_at->format('Y-m-d H:i') }}</td>
                                    <td class="px-4 py-2">{{ number_format($withdrawal->amount ?? 0, 2) }}</td>
                                    <td class="px-4 py-2">{{ $withdrawal->currency_code }}</td>
                                    <td class="px-4 py-2">{{ $withdrawal->payment_method_label }}</td>
                                    <td class="px-4 py-2">
                                        <div class="flex items-center gap-3">
                                            <span class="rounded bg-white/10 px-2 py-2 text-xs uppercase tracking-wide">
                                                {{ __('referrals::referrals.status_' . $withdrawal->status) }}
                                            </span>
                                            <details class="text-xs">
                                                <summary class="cursor-pointer text-primary-600 hover:text-primary-500 dark:text-primary-300">{{ __('referrals::referrals.details_button') }}</summary>
                                                <div class="mt-2 space-y-1">
                                                    @if ($withdrawal->admin_notes)
                                                        <p class="text-red-600 dark:text-red-200">{{ $withdrawal->admin_notes }}</p>
                                                    @endif
                                                    @if ($withdrawal->notes)
                                                        <p class="text-gray-600 dark:text-gray-300">{{ $withdrawal->notes }}</p>
                                                    @endif
                                                    @if ($withdrawal->payment_method_info)
                                                        <p class="text-gray-600 dark:text-gray-300">{{ __('referrals::referrals.withdrawal_payment_method_info') }}: {{ $withdrawal->payment_method_info }}</p>
                                                    @endif
                                                    <p class="text-gray-600 dark:text-gray-400">{{ __('referrals::referrals.withdrawal_requested_at', ['date' => $withdrawal->created_at->format('Y-m-d H:i')]) }}</p>
                                                </div>
                                            </details>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td class="px-4 py-4 text-center text-sm text-gray-600 dark:text-gray-400" colspan="5">
                                        {{ __('referrals::referrals.no_withdrawals_yet') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @else
            <div class="rounded-lg bg-background-secondary border border-neutral p-6 shadow">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white">{{ __('referrals::referrals.apply_title') }}</h2>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">{{ __('referrals::referrals.apply_intro') }}</p>
                @if ($requirementsCopy)
                    <div class="mt-4 rounded bg-background-secondary dark:bg-black/30 p-4 text-sm text-gray-700 dark:text-gray-200">
                        {!! nl2br(e($requirementsCopy)) !!}
                    </div>
                @endif

                @if ($application && $application->status === \Paymenter\Extensions\Others\ReferralSystem\Models\ReferralApplication::STATUS_PENDING)
                    <div class="mt-6 rounded border border-yellow-400/40 bg-yellow-500/10 p-4 text-sm text-yellow-700 dark:text-yellow-200">
                        <p class="font-semibold">{{ __('referrals::referrals.pending_title') }}</p>
                        <p>{{ __('referrals::referrals.pending_desc') }}</p>
                    </div>
                @elseif ($application && $application->status === \Paymenter\Extensions\Others\ReferralSystem\Models\ReferralApplication::STATUS_REJECTED)
                    <div class="mt-6 rounded border border-red-400/40 bg-red-500/10 p-4 text-sm text-red-600 dark:text-red-200">
                        <p class="font-semibold">{{ __('referrals::referrals.rejected_title') }}</p>
                        <p>{{ $application->admin_notes ?: __('referrals::referrals.rejected_desc') }}</p>
                    </div>
                    @if ($canApply)
                        <form wire:submit.prevent="submitApplication" class="mt-6 space-y-4">
                            <x-form.textarea name="message" label="{{ __('referrals::referrals.application_message') }}" rows="5" wire:model.defer="message" />
                            @if ($allowCustomCodes)
                                <x-form.input name="requested_code" label="{{ __('referrals::referrals.requested_code') }}" wire:model.defer="requested_code" />
                            @endif
                            <x-form.input name="desired_revenue_share" label="{{ __('referrals::referrals.desired_revenue_share') }}" type="number" min="0" max="100" step="0.5" wire:model.defer="desired_revenue_share" />
                            <x-button.primary type="submit" class="!w-fit">
                                {{ __('referrals::referrals.submit_application') }}
                            </x-button.primary>
                        </form>
                    @endif
                @elseif ($canApply)
                    <form wire:submit.prevent="submitApplication" class="mt-6 space-y-4">
                        <x-form.textarea name="message" label="{{ __('referrals::referrals.application_message') }}" rows="5" wire:model.defer="message" />
                        @if ($allowCustomCodes)
                            <x-form.input name="requested_code" label="{{ __('referrals::referrals.requested_code') }}" wire:model.defer="requested_code" />
                        @endif
                        <x-form.input name="desired_revenue_share" label="{{ __('referrals::referrals.desired_revenue_share') }}" type="number" min="0" max="100" step="0.5" wire:model.defer="desired_revenue_share" />
                        <x-button.primary type="submit" class="!w-fit">
                            {{ __('referrals::referrals.submit_application') }}
                        </x-button.primary>
                    </form>
                @endif
            </div>
        @endif
    </div>
</div>
