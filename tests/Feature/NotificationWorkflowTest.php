<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\Company;
use App\Models\JobListing;
use App\Models\User;
use App\Notifications\ApplicationStatusChangedNotification;
use App\Notifications\InterviewReminderNotification;
use App\Notifications\InterviewScheduledNotification;
use App\Notifications\JobAlertMatchesNotification;
use App\Notifications\NewApplicationNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class NotificationWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private function setupJob(): array
    {
        $employer = User::factory()->create(['role' => 'employer', 'email_verified_at' => now()]);
        $candidate = User::factory()->create(['role' => 'candidate', 'email_verified_at' => now()]);
        $company = Company::create(['owner_id' => $employer->id, 'name' => 'Acme', 'slug' => 'acme', 'verification_status' => 'verified']);
        $job = JobListing::create(['company_id' => $company->id, 'created_by' => $employer->id, 'title' => 'Engineer', 'slug' => 'engineer', 'description' => 'Build products', 'city' => 'Lagos', 'status' => 'published', 'application_type' => 'internal', 'published_at' => now()]);

        return compact('employer', 'candidate', 'job');
    }

    public function test_application_lifecycle_notifies_employer_and_candidate(): void
    {
        Notification::fake();
        extract($this->setupJob());
        $this->actingAs($candidate)->post(route('applications.store', $job), [])->assertSessionHasNoErrors();
        Notification::assertSentTo($employer, NewApplicationNotification::class);
        $application = Application::firstOrFail();
        $this->actingAs($employer)->patch(route('employer.applications.status', $application), ['status' => 'shortlisted'])->assertSessionHasNoErrors();
        Notification::assertSentTo($candidate, ApplicationStatusChangedNotification::class);
        $this->actingAs($employer)->post(route('employer.interviews.store', $application), ['scheduled_at' => now()->addDays(2)->toDateTimeString(), 'type' => 'video', 'location_or_url' => 'https://meet.example.test'])->assertSessionHasNoErrors();
        Notification::assertSentTo($candidate, InterviewScheduledNotification::class);
    }

    public function test_job_alert_command_delivers_each_match_once(): void
    {
        Notification::fake();
        extract($this->setupJob());
        $alert = DB::table('job_alerts')->insertGetId(['user_id' => $candidate->id, 'name' => 'Lagos engineering', 'filters' => json_encode(['keyword' => 'Engineer', 'location' => 'Lagos']), 'frequency' => 'daily', 'active' => true, 'created_at' => now(), 'updated_at' => now()]);
        Artisan::call('job-alerts:send');
        Notification::assertSentTo($candidate, JobAlertMatchesNotification::class);
        $this->assertDatabaseHas('job_alert_deliveries', ['job_alert_id' => $alert, 'job_listing_id' => $job->id]);
        Artisan::call('job-alerts:send');
        Notification::assertSentToTimes($candidate, JobAlertMatchesNotification::class, 1);
    }

    public function test_interview_reminder_command_marks_delivery(): void
    {
        Notification::fake();
        extract($this->setupJob());
        $application = Application::create(['job_listing_id' => $job->id, 'candidate_id' => $candidate->id, 'status' => 'interview']);
        $interview = DB::table('interviews')->insertGetId(['application_id' => $application->id, 'scheduled_at' => now()->addHours(12), 'type' => 'video', 'status' => 'scheduled', 'created_at' => now(), 'updated_at' => now()]);
        Artisan::call('interviews:send-reminders');
        Notification::assertSentTo($candidate, InterviewReminderNotification::class);
        $this->assertNotNull(DB::table('interviews')->find($interview)->reminder_sent_at);
    }

    public function test_user_can_update_notification_preferences(): void
    {
        $user = User::factory()->create(['role' => 'candidate', 'email_verified_at' => now()]);
        $this->actingAs($user)->patch(route('notifications.preferences'), ['in_app' => true, 'email' => false, 'application_updates' => true, 'job_alerts' => false, 'interview_reminders' => true])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('notification_preferences', ['user_id' => $user->id, 'email' => false, 'job_alerts' => false]);
    }
}
