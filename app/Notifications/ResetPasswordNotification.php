<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use LogicException;

class ResetPasswordNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly string $token) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        if (! $notifiable instanceof User) {
            throw new LogicException('Password reset notifications require a user.');
        }

        $arabic = ($notifiable->preferred_locale ?? 'en') === 'ar';
        $url = route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ]);

        return (new MailMessage)
            ->subject($arabic ? 'إعادة تعيين كلمة مرور بوابة العقارات' : 'Reset your property portal password')
            ->greeting($arabic ? 'مرحباً '.$notifiable->name : 'Hello '.$notifiable->name)
            ->line($arabic
                ? 'تلقينا طلباً لإعادة تعيين كلمة مرور حسابك.'
                : 'We received a request to reset your account password.')
            ->action($arabic ? 'إعادة تعيين كلمة المرور' : 'Reset password', $url)
            ->line($arabic
                ? 'ينتهي هذا الرابط خلال 60 دقيقة. تجاهل الرسالة إذا لم تطلب إعادة التعيين.'
                : 'This link expires in 60 minutes. Ignore this email if you did not request a reset.');
    }
}
