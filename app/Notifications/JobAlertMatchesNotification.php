<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class JobAlertMatchesNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public object $alert, public array $jobs) {}

    public function via(object $notifiable): array
    {
        $p = $notifiable->notificationPreference;
        if ($p?->job_alerts === false) {
            return [];
        }

        return array_values(array_filter([$p?->in_app === false ? null : 'database', $p?->email === false ? null : 'mail']));
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)->subject(count($this->jobs).' new jobs for '.$this->alert->name)->greeting('Hello '.$notifiable->name)->line('We found '.count($this->jobs).' new opportunities matching your alert.')->action('Browse matching jobs', route('jobs.index', $this->alert->filters));
    }

    public function toArray(object $notifiable): array
    {
        return ['kind' => 'job_alert', 'title' => 'New jobs for '.$this->alert->name, 'message' => count($this->jobs).' new opportunities match your alert.', 'url' => route('jobs.index', $this->alert->filters), 'job_ids' => array_column($this->jobs, 'id')];
    }
}
