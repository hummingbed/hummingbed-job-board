<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InterviewReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public object $interview) {}

    public function via(object $notifiable): array
    {
        $p = $notifiable->notificationPreference;
        if ($p?->interview_reminders === false) {
            return [];
        }

        return array_values(array_filter([$p?->in_app === false ? null : 'database', $p?->email === false ? null : 'mail']));
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)->subject('Interview reminder')->greeting('Hello '.$notifiable->name)->line('Your interview for '.$this->interview->job_title.' is scheduled for '.$this->interview->scheduled_at.'.')->action('View schedule', route('candidate.workspace'));
    }

    public function toArray(object $notifiable): array
    {
        return ['kind' => 'interview_reminder', 'title' => 'Interview reminder', 'message' => 'Your interview for '.$this->interview->job_title.' is scheduled for '.$this->interview->scheduled_at.'.', 'url' => route('candidate.workspace')];
    }
}
