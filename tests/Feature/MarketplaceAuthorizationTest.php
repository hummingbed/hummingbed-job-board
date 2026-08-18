<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\JobListing;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarketplaceAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_candidate_cannot_access_employer_job_creation(): void
    {
        $candidate = User::factory()->create(['role'=>'candidate','email_verified_at'=>now()]);
        $this->actingAs($candidate)->post(route('employer.jobs.store'), [])->assertForbidden();
    }

    public function test_employer_can_submit_a_job_for_moderation(): void
    {
        $employer = User::factory()->create(['role'=>'employer','email_verified_at'=>now()]);
        $company = Company::create(['owner_id'=>$employer->id,'name'=>'Acme','slug'=>'acme','verification_status'=>'verified']);
        $this->actingAs($employer)->post(route('employer.jobs.store'), ['company_id'=>$company->id,'title'=>'Backend Engineer','description'=>'Build reliable products','employment_type'=>'full_time','workplace_type'=>'remote','application_type'=>'internal'])->assertRedirect();
        $this->assertDatabaseHas('job_listings',['title'=>'Backend Engineer','status'=>'pending']);
    }

    public function test_admin_can_publish_a_pending_job(): void
    {
        $admin=User::factory()->create(['role'=>'admin','email_verified_at'=>now()]);
        $employer=User::factory()->create(['role'=>'employer']);
        $company=Company::create(['owner_id'=>$employer->id,'name'=>'Acme','slug'=>'acme']);
        $job=JobListing::create(['company_id'=>$company->id,'created_by'=>$employer->id,'title'=>'Engineer','slug'=>'engineer','description'=>'Role','status'=>'pending']);
        $this->actingAs($admin)->patch(route('admin.jobs.moderate',$job),['status'=>'published'])->assertRedirect();
        $this->assertDatabaseHas('job_listings',['id'=>$job->id,'status'=>'published']);
        $this->assertDatabaseHas('audit_logs',['action'=>'job.published']);
    }

    public function test_dummy_billing_activates_a_subscription(): void
    {
        $employer=User::factory()->create(['role'=>'employer','email_verified_at'=>now()]);
        $company=Company::create(['owner_id'=>$employer->id,'name'=>'Acme','slug'=>'acme']);
        $plan=Plan::create(['name'=>'Growth','price_cents'=>4900,'job_credits'=>10]);
        $this->actingAs($employer)->post(route('dummy-billing.subscribe',[$company,$plan]))->assertRedirect();
        $this->assertDatabaseHas('subscriptions',['company_id'=>$company->id,'plan_id'=>$plan->id,'status'=>'active']);
    }
}
