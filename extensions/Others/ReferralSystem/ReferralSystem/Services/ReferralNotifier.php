<?php

namespace Paymenter\Extensions\Others\ReferralSystem\Services;

use App\Helpers\ExtensionHelper;
use App\Helpers\NotificationHelper;
use App\Mail\SystemMail;
use App\Models\EmailLog;
use App\Models\NotificationTemplate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Paymenter\Extensions\Others\ReferralSystem\Models\ReferralApplication;
use Paymenter\Extensions\Others\ReferralSystem\Models\ReferralCode;
use Paymenter\Extensions\Others\ReferralSystem\Models\ReferralCommission;
use Paymenter\Extensions\Others\ReferralSystem\Models\ReferralWithdrawal;

class ReferralNotifier
{
    public static function sendApplicationDecision(ReferralApplication $application, string $decision): void
    {
        $extension = ExtensionHelper::getExtension('other', 'ReferralSystem');

        if (!$extension->config('auto_notify_on_approval')) {
            return;
        }

        $user = $application->user;

        if (!$user || empty($user->email)) {
            return;
        }

        $code = $application->referralCode;
        $templateKey = $decision === 'approved'
            ? 'referral_application_approved'
            : 'referral_application_rejected';

        if (self::notificationTemplateExists($templateKey)) {
            NotificationHelper::sendNotification($templateKey, [
                'user' => $user,
                'application' => $application,
                'code' => $code,
            ], $user);

            return;
        }

        $subject = __('referrals::referrals.email_application_subject_' . $decision);
        $body = view('referrals::mail.application-' . $decision, [
            'user' => $user,
            'application' => $application,
            'code' => $code,
        ])->render();

        self::sendEmail($user->email, $subject, $body, $user->id);
    }

    public static function sendCommissionEarned(ReferralCode $code, ReferralCommission $commission): void
    {
        $extension = ExtensionHelper::getExtension('other', 'ReferralSystem');

        if (!$extension->config('auto_notify_on_purchase')) {
            return;
        }

        $user = $code->user;

        if (!$user || empty($user->email)) {
            return;
        }

        if (self::notificationTemplateExists('referral_commission_earned')) {
            NotificationHelper::sendNotification('referral_commission_earned', [
                'user' => $user,
                'commission' => $commission,
                'code' => $code,
            ], $user);

            return;
        }

        $context = match ($commission->source_type) {
            ReferralCommission::SOURCE_MANUAL => 'manual',
            ReferralCommission::SOURCE_RECURRING => 'recurring',
            default => 'invoice',
        };

        $subject = __('referrals::referrals.email_commission_subject_' . $context, ['code' => $code->code]);
        $body = view('referrals::mail.commission-earned', [
            'user' => $user,
            'commission' => $commission,
            'code' => $code,
            'context' => $context,
        ])->render();

        self::sendEmail($user->email, $subject, $body, $user->id);
    }

    public static function sendWithdrawalUpdate(ReferralWithdrawal $withdrawal): void
    {
        $extension = ExtensionHelper::getExtension('other', 'ReferralSystem');

        if (!$extension->config('auto_notify_on_withdrawal_update')) {
            return;
        }

        $user = $withdrawal->user;

        if (!$user || empty($user->email)) {
            return;
        }

        if (self::notificationTemplateExists('referral_withdrawal_updated')) {
            NotificationHelper::sendNotification('referral_withdrawal_updated', [
                'user' => $user,
                'withdrawal' => $withdrawal,
            ], $user);

            return;
        }

        $subject = __('referrals::referrals.email_withdrawal_subject', ['status' => __('referrals::referrals.status_' . $withdrawal->status)]);
        $body = view('referrals::mail.withdrawal-updated', [
            'user' => $user,
            'withdrawal' => $withdrawal,
        ])->render();

        self::sendEmail($user->email, $subject, $body, $user->id);
    }

    /**
     * Send email and log it to Paymenter's EmailLog
     */
    protected static function sendEmail(string $email, string $subject, string $body, ?int $userId = null): void
    {
        if (config('settings.mail_disable')) {
            return;
        }

        try {
            $mail = new SystemMail([
                'subject' => $subject,
                'body' => $body,
            ]);

            // Log the email to Paymenter's mail log
            $emailLog = EmailLog::create([
                'user_id' => $userId,
                'subject' => $subject,
                'to' => $email,
                'body' => $mail->render(),
            ]);

            $mail->email_log_id = $emailLog->id;

            Mail::to($email)->queue($mail);
        } catch (\Throwable $e) {
            Log::warning('Referral notification mail failed', [
                'email' => $email,
                'subject' => $subject,
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected static function notificationTemplateExists(string $key): bool
    {
        return NotificationTemplate::query()
            ->where('key', $key)
            ->where('enabled', true)
            ->exists();
    }
}
