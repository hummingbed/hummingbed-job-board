<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\Company;
use App\Models\JobCategory;
use App\Models\JobListing;
use App\Models\User;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

class HomeController extends Controller
{
    public function __invoke()
    {
        return Inertia::render('Welcome', [
            'canLogin' => Route::has('login'), 'canRegister' => Route::has('register'),
            'recentJobs' => JobListing::published()->with(['company:id,name,logo_path', 'category:id,name'])->latest('published_at')->limit(5)->get(),
            'categories' => JobCategory::withCount(['jobs' => fn ($q) => $q->published()])->orderByDesc('jobs_count')->limit(8)->get(),
            'featuredCompanies' => Company::withCount(['jobs' => fn ($q) => $q->published()])->where('verification_status', 'verified')->orderByDesc('is_featured')->limit(5)->get(),
            'locations' => JobListing::published()->whereNotNull('city')->distinct()->orderBy('city')->pluck('city'),
            'stats' => ['jobs' => JobListing::published()->count(), 'candidates' => User::where('role', 'candidate')->count(), 'companies' => Company::where('verification_status', 'verified')->count(), 'applications' => Application::count()],
        ]);
    }
}
