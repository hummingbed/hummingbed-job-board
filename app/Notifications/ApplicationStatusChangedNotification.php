<?php

namespace App\Notifications;

use App\Models\Application;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ApplicationStatusChangedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Application $application, public string $previousStatus) {}

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
        $job = $this->application->jobListing;

        return (new MailMessage)->subject('Application update: '.$job->title)->greeting('Hello '.$notifiable->name)->line('Your application status changed to '.ucfirst($this->application->status).'.')->action('View applications', route('candidate.workspace'));
    }

    public function toArray(object $notifiable): array
    {
        $job = $this->application->jobListing;

        return ['kind' => 'application_status', 'title' => 'Application updated', 'message' => $job->title.' moved from '.$this->previousStatus.' to '.$this->application->status.'.', 'url' => route('candidate.workspace'), 'application_id' => $this->application->id];
    }
}
