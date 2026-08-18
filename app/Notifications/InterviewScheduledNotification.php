<?php

namespace App\Notifications;

use App\Models\Application;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InterviewScheduledNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Application $application, public array $interview) {}

    public function via(object $notifiable): array
    {
        $p = $notifiable->notificationPreference;
        if ($p?->application_updates === false) {
            return [];
        }

        return array_values(array_filter([$p?->in_app === false ? null : 'database', $p?->email === false ? null : 'mail']));
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)->subject('Interview scheduled')->greeting('Hello '.$notifiable->name)->line('Your interview for '.$this->application->jobListing->title.' is scheduled for '.$this->interview['scheduled_at'].'.')->action('View interview', route('candidate.workspace'));
    }

    public function toArray(object $notifiable): array
    {
        return ['kind' => 'interview_scheduled', 'title' => 'Interview scheduled', 'message' => 'Your interview for '.$this->application->jobListing->title.' is scheduled for '.$this->interview['scheduled_at'].'.', 'url' => route('candidate.workspace'), 'application_id' => $this->application->id];
    }
}
