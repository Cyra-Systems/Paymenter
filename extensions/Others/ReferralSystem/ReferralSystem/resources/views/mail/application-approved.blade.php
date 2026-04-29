# {{ __('referrals::referrals.email_application_heading_approved') }}

{{ __('referrals::referrals.email_application_intro_approved') }}

@if ($code)
| {{ __('referrals::referrals.share_code_title') }} |  |
| :------------------------------------------------ | :------------------------------------------------ |
| {{ __('referrals::referrals.email_application_code') }} | `{{ $code->code }}` |
| {{ __('referrals::referrals.revenue_share_default') }} | {{ $code->default_revenue_share }}% |
@endif

{{ __('referrals::referrals.email_application_footer') }}
