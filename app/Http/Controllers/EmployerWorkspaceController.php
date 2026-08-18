<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\JobCategory;
use App\Models\JobListing;
use App\Models\Plan;
use Illuminate\Http\Request;
use Inertia\Inertia;

class EmployerWorkspaceController extends Controller
{
    private function companyIds(Request $r)
    {
        return Company::where('owner_id', $r->user()->id)->orWhereHas('members', fn ($q) => $q->where('users.id', $r->user()->id))->pluck('id');
    }

    public function index(Request $r)
    {
        $ids = $this->companyIds($r);
        $companies = Company::with(['subscriptions' => fn ($q) => $q->where('status', 'active')->with('plan')])->whereIn('id', $ids)->get();
        $jobs = JobListing::with('company')->withCount('applications')->whereIn('company_id', $ids)->latest()->get();

        return Inertia::render('Employer/Workspace', ['companies' => $companies, 'jobs' => $jobs, 'plans' => Plan::where('active', true)->get(), 'metrics' => ['activeJobs' => $jobs->where('status', 'published')->count(), 'pendingJobs' => $jobs->where('status', 'pending')->count(), 'applications' => $jobs->sum('applications_count'), 'companies' => $companies->count()]]);
    }

    public function create(Request $r)
    {
        return Inertia::render('Employer/JobForm', ['companies' => Company::whereIn('id', $this->companyIds($r))->get(['id', 'name']), 'categories' => JobCategory::orderBy('name')->get(['id', 'name'])]);
    }

    public function edit(Request $r, JobListing $job)
    {
        abort_unless($this->companyIds($r)->contains($job->company_id), 403);

        return Inertia::render('Employer/JobForm', ['job' => $job, 'companies' => Company::whereIn('id', $this->companyIds($r))->get(['id', 'name']), 'categories' => JobCategory::orderBy('name')->get(['id', 'name'])]);
    }

    public function applicants(Request $r, JobListing $job)
    {
        abort_unless($this->companyIds($r)->contains($job->company_id), 403);
        $job->load(['company', 'applications.candidate.candidateProfile', 'applications.history']);

        return Inertia::render('Employer/Applicants',['job' => $job]);
    }
}
