@php
$amount = number_format($withdrawal->amount ?? 0, 2);
@endphp

# {{ __('referrals::referrals.email_withdrawal_heading', ['status' => __('referrals::referrals.status_' . $withdrawal->status)]) }}

{{ __('referrals::referrals.email_withdrawal_intro', ['status' => __('referrals::referrals.status_' . $withdrawal->status)]) }}

| {{ __('referrals::referrals.table_amount') }} | {{ __('referrals::referrals.table_currency') }} | {{ __('referrals::referrals.table_requested') }} | {{ __('referrals::referrals.withdrawal_payment_method') }} |
| :-------------------------------- | :----------------------------------- | :----------------------------------- | :----------------------------------- |
| {{ $amount }} | {{ $withdrawal->currency_code }} | {{ $withdrawal->created_at->format('Y-m-d H:i') }} | {{ $withdrawal->payment_method_label }} |

@if ($withdrawal->payment_method_info)
> {{ __('referrals::referrals.withdrawal_payment_method_info') }}: {{ $withdrawal->payment_method_info }}
@endif

@if ($withdrawal->admin_notes)
> {{ $withdrawal->admin_notes }}
@endif

{{ __('referrals::referrals.email_withdrawal_footer') }}
