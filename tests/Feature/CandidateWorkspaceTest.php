<?php

namespace Tests\Feature;

use App\Models\Resume;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CandidateWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_candidate_can_open_workspace_and_employer_cannot(): void
    {
        $candidate = User::factory()->create(['role' => 'candidate', 'email_verified_at' => now()]);
        $employer = User::factory()->create(['role' => 'employer', 'email_verified_at' => now()]);

        $this->actingAs($candidate)->get(route('candidate.workspace'))->assertOk();
        $this->actingAs($employer)->get(route('candidate.workspace'))->assertForbidden();
    }

    public function test_candidate_can_upload_and_manage_own_resumes(): void
    {
        Storage::fake('public');
        $candidate = User::factory()->create(['role' => 'candidate', 'email_verified_at' => now()]);

        $this->actingAs($candidate)->post(route('candidate.resumes.store'), [
            'name' => 'Primary résumé', 'resume' => UploadedFile::fake()->create('resume.pdf', 100, 'application/pdf'),
        ])->assertSessionHasNoErrors();

        $resume = Resume::firstOrFail();
        $this->assertTrue($resume->is_default);
        Storage::disk('public')->assertExists($resume->path);

        $other = User::factory()->create(['role' => 'candidate', 'email_verified_at' => now()]);
        $this->actingAs($other)->delete(route('candidate.resumes.destroy', $resume))->assertForbidden();
    }

    public function test_candidate_dashboard_redirects_to_candidate_workspace(): void
    {
        $candidate = User::factory()->create(['role' => 'candidate', 'email_verified_at' => now()]);
        $this->actingAs($candidate)->get(route('dashboard'))->assertRedirect(route('candidate.workspace'));
    }
}
