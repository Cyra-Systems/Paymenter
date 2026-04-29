# {{ __('referrals::referrals.email_application_heading_rejected') }}

{{ __('referrals::referrals.email_application_intro_rejected') }}

@if ($application->admin_notes)
> {{ $application->admin_notes }}
@endif

{{ __('referrals::referrals.email_application_footer_rejected') }}
