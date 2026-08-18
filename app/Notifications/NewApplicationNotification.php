<?php

namespace App\Notifications;

use App\Models\Application;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewApplicationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Application $application) {}

    public function via(object $notifiable): array
    {
        $p = $notifiable->notificationPreference;

        return array_values(array_filter([$p?->in_app === false ? null : 'database', $p?->email === false ? null : 'mail']));
    }

    public function toMail(object $notifiable): MailMessage
    {
        $job = $this->application->jobListing;

        return (new MailMessage)->subject('New application for '.$job->title)->greeting('Hello '.$notifiable->name)->line($this->application->candidate->name.' applied for '.$job->title.'.')->action('Review applicant', route('employer.jobs.applicants', $job));
    }

    public function toArray(object $notifiable): array
    {
        $job = $this->application->jobListing;

        return ['kind' => 'new_application', 'title' => 'New application', 'message' => $this->application->candidate->name.' applied for '.$job->title.'.', 'url' => route('employer.jobs.applicants', $job), 'application_id' => $this->application->id];
    }
}
