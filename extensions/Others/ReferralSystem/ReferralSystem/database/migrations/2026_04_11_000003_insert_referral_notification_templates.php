<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const TEMPLATES = [
        'referral_application_approved' => [
            'subject' => 'Your referral application has been approved',
            'body' => <<<'HTML'
                # Referral application approved

                Your referral application has been approved.

                **Referral code**
                - Code: {{ $code->code }}
                - Default share: {{ $code->default_revenue_share }}%

                <div class="action">
                    <a class="button button-blue" href="{{ route('referrals.dashboard') }}">
                        Open referral dashboard
                    </a>
                </div>
                HTML,
            'mail_enabled' => 'choice_on',
            'in_app_enabled' => 'choice_on',
            'in_app_title' => 'Referral application approved',
            'in_app_body' => 'Your referral application was approved. Your code {{ $code->code }} is now active.',
            'in_app_url' => '{{ route("referrals.dashboard") }}',
            'edit_preference_message' => 'Notify me when my referral application is approved',
        ],
        'referral_application_rejected' => [
            'subject' => 'Update on your referral application',
            'body' => <<<'HTML'
                # Referral application update

                We reviewed your referral application and it was not approved at this time.

                @if($application->decision_notes)
                **Reason**
                {{ $application->decision_notes }}
                @endif

                <div class="action">
                    <a class="button button-blue" href="{{ route('referrals.dashboard') }}">
                        Open referral dashboard
                    </a>
                </div>
                HTML,
            'mail_enabled' => 'choice_on',
            'in_app_enabled' => 'choice_on',
            'in_app_title' => 'Referral application update',
            'in_app_body' => 'Your referral application was declined. Review the latest update in your referral dashboard.',
            'in_app_url' => '{{ route("referrals.dashboard") }}',
            'edit_preference_message' => 'Notify me when my referral application is rejected',
        ],
        'referral_commission_earned' => [
            'subject' => 'New referral commission for code {{ $code->code }}',
            'body' => <<<'HTML'
                # New referral commission

                A new referral commission was added to your account.

                **Details**
                - Code: {{ $code->code }}
                - Source: {{ $commission->sourceLabel() }}
                - Amount: {{ number_format($commission->amount, 2) }} {{ $commission->currency_code }}
                - Awarded: {{ optional($commission->awarded_at)->format('Y-m-d H:i') ?? now()->format('Y-m-d H:i') }}

                @if($commission->invoice_id)
                - Invoice: #{{ $commission->invoice_id }}
                @endif

                <div class="action">
                    <a class="button button-blue" href="{{ route('referrals.dashboard') }}">
                        Open referral dashboard
                    </a>
                </div>
                HTML,
            'mail_enabled' => 'choice_on',
            'in_app_enabled' => 'choice_on',
            'in_app_title' => 'New referral commission',
            'in_app_body' => 'You received {{ number_format($commission->amount, 2) }} {{ $commission->currency_code }} from {{ $commission->sourceLabel() }}.',
            'in_app_url' => '{{ route("referrals.dashboard") }}',
            'edit_preference_message' => 'Notify me when I earn referral commissions',
        ],
        'referral_withdrawal_updated' => [
            'subject' => 'Referral withdrawal status: {{ __("referrals::referrals.status_" . $withdrawal->status) }}',
            'body' => <<<'HTML'
                # Referral withdrawal updated

                Your referral withdrawal request is now marked as {{ __('referrals::referrals.status_' . $withdrawal->status) }}.

                **Withdrawal details**
                - Amount: {{ number_format($withdrawal->amount, 2) }} {{ $withdrawal->currency_code }}
                - Payment method: {{ $withdrawal->payment_method_label }}

                @if($withdrawal->admin_notes)
                **Admin notes**
                {{ $withdrawal->admin_notes }}
                @endif

                <div class="action">
                    <a class="button button-blue" href="{{ route('referrals.dashboard') }}">
                        Open referral dashboard
                    </a>
                </div>
                HTML,
            'mail_enabled' => 'choice_on',
            'in_app_enabled' => 'choice_on',
            'in_app_title' => 'Referral withdrawal updated',
            'in_app_body' => 'Your referral withdrawal request for {{ number_format($withdrawal->amount, 2) }} {{ $withdrawal->currency_code }} is now {{ __("referrals::referrals.status_" . $withdrawal->status) }}.',
            'in_app_url' => '{{ route("referrals.dashboard") }}',
            'edit_preference_message' => 'Notify me when my referral withdrawals are updated',
        ],
    ];

    public function up(): void
    {
        foreach (self::TEMPLATES as $key => $data) {
            DB::table('notification_templates')->updateOrInsert(
                ['key' => $key],
                array_merge($data, ['enabled' => true])
            );
        }
    }

    public function down(): void
    {
        DB::table('notification_templates')
            ->whereIn('key', array_keys(self::TEMPLATES))
            ->delete();
    }
};
