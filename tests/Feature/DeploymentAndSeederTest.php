<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DeploymentAndSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_seeder_populates_workflow_tables_and_is_idempotent(): void
    {
        Storage::fake('local');
        $this->seed();
        $this->seed();

        foreach (['candidate_profiles','companies','company_members','skills','job_listings','job_listing_skill','resumes','applications','application_status_history','application_notes','saved_jobs','job_alerts','interviews','subscriptions','reports','audit_logs','analytics_events','newsletter_subscribers','contact_messages','notification_preferences','notifications','job_alert_deliveries'] as $table) {
            $this->assertDatabaseCountNotZero($table);
        }
        $this->assertDatabaseCount('companies', 3);
        $this->assertDatabaseCount('job_listings', 8);
    }

    public function test_vercel_cron_routes_require_the_configured_secret(): void
    {
        config(['services.vercel.cron_secret' => 'test-cron-secret']);
        $this->get(route('cron.job-alerts'))->assertUnauthorized();
        $this->withHeader('Authorization', 'Bearer test-cron-secret')->get(route('cron.job-alerts'))->assertOk()->assertJson(['ok' => true]);
    }

    private function assertDatabaseCountNotZero(string $table): void
    {
        $this->assertGreaterThan(0, DB::table($table)->count(), $table.' should contain demo data.');
    }
}
