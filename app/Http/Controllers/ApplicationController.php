<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\ApplicationStatusHistory;
use App\Models\JobListing;
use App\Notifications\ApplicationStatusChangedNotification;
use App\Notifications\NewApplicationNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ApplicationController extends Controller
{
    public function store(Request $r, JobListing $job)
    {
        abort_unless($job->status === 'published' && $job->application_type === 'internal', 422);
        abort_if($job->application_deadline && $job->application_deadline->lt(today()), 422, 'Applications for this job have closed.');
        $data = $r->validate(['resume_id' => ['nullable', Rule::exists('resumes', 'id')->where('user_id', $r->user()->id)], 'cover_letter' => 'nullable|max:5000']);
        if (empty($data['resume_id'])) {
            $data['resume_id'] = $r->user()->resumes()->where('is_default', true)->value('id');
        }
        $application = Application::firstOrCreate(['job_listing_id' => $job->id, 'candidate_id' => $r->user()->id], $data + ['status' => 'submitted']);
        if ($application->wasRecentlyCreated) {
            $application->load(['candidate', 'jobListing']);
            $job->creator->notify(new NewApplicationNotification($application));
        }

        return back()->with('success', $application->wasRecentlyCreated ? 'Application submitted successfully.' : 'You have already applied for this job.');
    }

    public function updateStatus(Request $r, Application $application)
    {
        $data = $r->validate(['status' => 'required|in:reviewing,shortlisted,interview,offer,hired,rejected', 'note' => 'nullable|max:2000']);
        abort_unless($application->jobListing->created_by === $r->user()->id || $r->user()->isAdmin(), 403);
        $old = $application->status;
        DB::transaction(function () use ($application, $data, $r, $old) {
            $application->update(['status' => $data['status']]);
            ApplicationStatusHistory::create(['application_id' => $application->id, 'changed_by' => $r->user()->id, 'from_status' => $old, 'to_status' => $data['status'], 'note' => $data['note'] ?? null]);
        });
        $application->load('jobListing');
        $application->candidate->notify(new ApplicationStatusChangedNotification($application, $old));

        return back()->with('success', 'Application status updated.');
    }

    public function withdraw(Request $r, Application $application)
    {
        abort_unless($application->candidate_id === $r->user()->id, 403);
        $application->update(['status' => 'withdrawn', 'withdrawn_at' => now()]);

        return back()->with('success', 'Application withdrawn.');
    }
}
