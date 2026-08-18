<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\Company;
use App\Models\JobCategory;
use App\Models\JobListing;
use App\Models\Resume;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PublicMarketplaceTest extends TestCase
{
    use RefreshDatabase;

    private function listing(array $overrides = []): JobListing
    {
        $employer = User::factory()->create(['role' => 'employer']);
        $company = Company::create(['owner_id' => $employer->id, 'name' => 'Acme', 'slug' => 'acme', 'verification_status' => 'verified']);
        $category = JobCategory::create(['name' => 'Technology', 'slug' => 'technology']);

        return JobListing::create(array_merge(['company_id' => $company->id, 'created_by' => $employer->id, 'job_category_id' => $category->id, 'title' => 'Vue Engineer', 'slug' => 'vue-engineer', 'description' => 'Build products', 'city' => 'Lagos', 'country' => 'Nigeria', 'employment_type' => 'full_time', 'salary_max' => 90000, 'status' => 'published', 'published_at' => now()], $overrides));
    }

    public function test_public_job_filters_return_database_listings(): void
    {
        $job = $this->listing();
        $this->get(route('jobs.index', ['q' => 'Vue', 'location' => 'Lagos']))->assertOk()->assertInertia(fn (Assert $page) => $page->component('Jobs')->where('databaseJobs.total', 1)->where('databaseJobs.data.0.id', $job->id));
    }

    public function test_job_detail_exposes_candidate_saved_and_application_state(): void
    {
        $job = $this->listing();
        $candidate = User::factory()->create(['role' => 'candidate', 'email_verified_at' => now()]);
        $candidate->belongsToMany(JobListing::class, 'saved_jobs')->attach($job);
        $resume = Resume::create(['user_id' => $candidate->id, 'name' => 'Main résumé', 'path' => 'test.pdf', 'is_default' => true]);
        Application::create(['job_listing_id' => $job->id, 'candidate_id' => $candidate->id, 'resume_id' => $resume->id, 'status' => 'submitted']);
        $this->actingAs($candidate)->get(route('jobs.show', $job->slug))->assertOk()->assertInertia(fn (Assert $page) => $page->component('JobDetails')->where('isSaved', true)->where('application.status', 'submitted')->has('resumes', 1));
    }

    public function test_public_contact_and_newsletter_forms_persist(): void
    {
        $job = $this->listing();
        $this->post(route('newsletter.store'), ['email' => 'reader@example.com'])->assertSessionHasNoErrors();
        $this->post(route('contact.store'), ['name' => 'Reader', 'email' => 'reader@example.com', 'message' => 'I have a question.', 'job_listing_id' => $job->id])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('newsletter_subscribers', ['email' => 'reader@example.com']);
        $this->assertDatabaseHas('contact_messages', ['job_listing_id' => $job->id, 'status' => 'new']);
    }
}
