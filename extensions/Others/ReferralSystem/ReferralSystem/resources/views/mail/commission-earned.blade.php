@php
$amount = number_format($commission->amount ?? 0, 2);
$context = $context ?? 'invoice';
@endphp

# {{ __('referrals::referrals.email_commission_heading_' . $context) }}

{{ __('referrals::referrals.email_commission_intro_' . $context, ['code' => $code->code, 'label' => $commission->sourceLabel()]) }}

| {{ __('referrals::referrals.table_amount') }} | {{ __('referrals::referrals.table_currency') }} | {{ __('referrals::referrals.table_order') }} | {{ __('referrals::referrals.table_awarded') }} |
| :-------------------------------------------- | :---------------------------------------------- | :-------------------------------------------- | :---------------------------------------------- |
| {{ $amount }} | {{ $commission->currency_code }} | {{ $commission->invoice_id ? '#'.$commission->invoice_id : ($commission->sourceLabel()) }} | {{ optional($commission->awarded_at)->format('Y-m-d H:i') ?? now()->format('Y-m-d H:i') }} |

{{ __('referrals::referrals.email_commission_footer') }}
