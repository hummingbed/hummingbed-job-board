<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\JobListing;
use App\Models\Resume;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class CandidateWorkspaceController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $applications = Application::with(['jobListing.company', 'jobListing.category'])
            ->where('candidate_id', $user->id)->latest('submitted_at')->get();
        $savedJobs = $user->belongsToMany(JobListing::class, 'saved_jobs')
            ->with(['company:id,name', 'category:id,name'])->withTimestamps()->latest('saved_jobs.created_at')->get();
        $interviews = DB::table('interviews')->join('applications', 'applications.id', '=', 'interviews.application_id')
            ->join('job_listings', 'job_listings.id', '=', 'applications.job_listing_id')
            ->join('companies', 'companies.id', '=', 'job_listings.company_id')
            ->where('applications.candidate_id', $user->id)->orderBy('interviews.scheduled_at')
            ->get(['interviews.*', 'job_listings.title as job_title', 'companies.name as company_name']);

        return Inertia::render('Candidate/Workspace', [
            'profile' => $user->candidateProfile,
            'applications' => $applications,
            'savedJobs' => $savedJobs,
            'resumes' => Resume::where('user_id', $user->id)->latest()->get(),
            'alerts' => DB::table('job_alerts')->where('user_id', $user->id)->latest()->get()->map(fn ($alert) => tap($alert, fn ($a) => $a->filters = json_decode($a->filters, true))),
            'interviews' => $interviews,
            'metrics' => [
                'Applications' => $applications->count(), 'Saved jobs' => $savedJobs->count(),
                'Interviews' => $interviews->where('status', 'scheduled')->count(),
                'Offers' => $applications->whereIn('status', ['offer', 'hired'])->count(),
            ],
        ]);
    }

    public function storeResume(Request $request)
    {
        $data = $request->validate(['name' => 'required|max:120', 'resume' => 'required|file|mimes:pdf,doc,docx|max:5120']);
        $path = $request->file('resume')->store('resumes/'.$request->user()->id);
        $first = ! Resume::where('user_id', $request->user()->id)->exists();
        Resume::create(['user_id' => $request->user()->id, 'name' => $data['name'], 'path' => $path, 'is_default' => $first]);

        return back()->with('success', 'Résumé uploaded.');
    }

    public function defaultResume(Request $request, Resume $resume)
    {
        abort_unless($resume->user_id === $request->user()->id, 403);
        DB::transaction(function () use ($request, $resume) {
            Resume::where('user_id', $request->user()->id)->update(['is_default' => false]);
            $resume->update(['is_default' => true]);
        });

        return back()->with('success', 'Default résumé updated.');
    }

    public function destroyResume(Request $request, Resume $resume)
    {
        abort_unless($resume->user_id === $request->user()->id, 403);
        Storage::delete($resume->path);
        $wasDefault = $resume->is_default;
        $resume->delete();
        if ($wasDefault) {
            Resume::where('user_id', $request->user()->id)->latest()->first()?->update(['is_default' => true]);
        }

        return back()->with('success', 'Résumé removed.');
    }

    public function toggleAlert(Request $request, int $alert)
    {
        $row = DB::table('job_alerts')->where('id', $alert)->where('user_id', $request->user()->id)->first();
        abort_unless($row, 404);
        DB::table('job_alerts')->where('id', $alert)->update(['active' => ! $row->active, 'updated_at' => now()]);

        return back()->with('success', 'Job alert updated.');
    }

    public function destroyAlert(Request $request, int $alert)
    {
        DB::table('job_alerts')->where('id', $alert)->where('user_id', $request->user()->id)->delete();

        return back()->with('success', 'Job alert deleted.');
    }
}
