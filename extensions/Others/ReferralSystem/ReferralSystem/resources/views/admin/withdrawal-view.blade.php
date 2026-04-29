<div class="space-y-4">
    <div>
        <h3 class="text-base font-semibold text-white">Withdrawal</h3>
        <dl class="mt-2 text-sm text-gray-200 space-y-1">
            <div class="flex justify-between gap-4">
                <dt>Requested</dt>
                <dd>{{ $withdrawal->created_at?->format('Y-m-d H:i') ?? '—' }}</dd>
            </div>
            <div class="flex justify-between gap-4">
                <dt>Amount</dt>
                <dd>{{ number_format($withdrawal->amount, 2) }} {{ $withdrawal->currency_code }}</dd>
            </div>
            <div class="flex justify-between gap-4">
                <dt>Status</dt>
                <dd>{{ __('referrals::referrals.status_' . $withdrawal->status) }}</dd>
            </div>
            <div class="flex justify-between gap-4">
                <dt>Payment Method</dt>
                <dd>{{ $withdrawal->payment_method_label }}</dd>
            </div>
        </dl>
    </div>

    @if ($code)
        <div>
            <h3 class="text-base font-semibold text-white">Referral Code</h3>
            <dl class="mt-2 text-sm text-gray-200 space-y-1">
                <div class="flex justify-between gap-4">
                    <dt>Code</dt>
                    <dd>{{ $code->code }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt>Owner</dt>
                    <dd>{{ $user?->name }} ({{ $user?->email }})</dd>
                </div>
            </dl>
        </div>

        <div>
            <h3 class="text-base font-semibold text-white">Commission Totals</h3>
            <dl class="mt-2 text-sm text-gray-200 space-y-1">
                <div class="flex justify-between gap-4">
                    <dt>Total Earned</dt>
                    <dd>{{ number_format($totals['total'], 2) }} {{ $withdrawal->currency_code }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt>Available</dt>
                    <dd>{{ number_format($totals['available'], 2) }} {{ $withdrawal->currency_code }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt>Reserved</dt>
                    <dd>{{ number_format($totals['reserved'], 2) }} {{ $withdrawal->currency_code }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt>Paid</dt>
                    <dd>{{ number_format($totals['paid'], 2) }} {{ $withdrawal->currency_code }}</dd>
                </div>
            </dl>
        </div>
    @endif

    @if ($withdrawal->payment_method_info)
        <div>
            <h3 class="text-base font-semibold text-white">Payment Method Information</h3>
            <p class="whitespace-pre-line text-sm text-gray-200">{{ $withdrawal->payment_method_info }}</p>
        </div>
    @endif

    @if ($withdrawal->notes)
        <div>
            <h3 class="text-base font-semibold text-white">User Notes</h3>
            <p class="whitespace-pre-line text-sm text-gray-200">{{ $withdrawal->notes }}</p>
        </div>
    @endif

    @if ($withdrawal->admin_notes)
        <div>
            <h3 class="text-base font-semibold text-white">Admin Notes</h3>
            <p class="whitespace-pre-line text-sm text-red-200">{{ $withdrawal->admin_notes }}</p>
        </div>
    @endif
</div>
