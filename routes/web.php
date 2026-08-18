<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\ApplicationController;
use App\Http\Controllers\CandidateWorkspaceController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DummyBillingController;
use App\Http\Controllers\EmployerJobController;
use App\Http\Controllers\EmployerWorkspaceController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\InterviewController;
use App\Http\Controllers\MarketplaceController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\VercelCronController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicFormController;
use App\Http\Controllers\PublicJobController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::post('/newsletter', [PublicFormController::class, 'newsletter'])->name('newsletter.store');
Route::post('/contact', [PublicFormController::class, 'contact'])->name('contact.store');
Route::get('/api/cron/job-alerts', [VercelCronController::class, 'jobAlerts'])->name('cron.job-alerts');
Route::get('/api/cron/interview-reminders', [VercelCronController::class, 'interviewReminders'])->name('cron.interview-reminders');

Route::get('/jobs', [PublicJobController::class, 'index'])->name('jobs.index');
Route::get('/jobs/{job:slug}', [PublicJobController::class, 'show'])->name('jobs.show');

Route::get('/dashboard', DashboardController::class)->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::patch('/notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.read-all');
    Route::patch('/notifications/preferences', [NotificationController::class, 'preferences'])->name('notifications.preferences');
    Route::patch('/notifications/{notification}/read', [NotificationController::class, 'read'])->name('notifications.read');
    Route::delete('/notifications/{notification}', [NotificationController::class, 'destroy'])->name('notifications.destroy');
    Route::middleware('role:candidate')->prefix('candidate')->group(function () {
        Route::get('/', [CandidateWorkspaceController::class, 'index'])->name('candidate.workspace');
        Route::post('/resumes', [CandidateWorkspaceController::class, 'storeResume'])->name('candidate.resumes.store');
        Route::patch('/resumes/{resume}/default', [CandidateWorkspaceController::class, 'defaultResume'])->name('candidate.resumes.default');
        Route::delete('/resumes/{resume}', [CandidateWorkspaceController::class, 'destroyResume'])->name('candidate.resumes.destroy');
        Route::patch('/alerts/{alert}/toggle', [CandidateWorkspaceController::class, 'toggleAlert'])->name('candidate.alerts.toggle');
        Route::delete('/alerts/{alert}', [CandidateWorkspaceController::class, 'destroyAlert'])->name('candidate.alerts.destroy');
    });
    Route::patch('/candidate/profile', [MarketplaceController::class, 'profile'])->middleware('role:candidate')->name('candidate.profile');
    Route::post('/companies', [MarketplaceController::class, 'company'])->middleware('role:employer,admin')->name('companies.store');
    Route::post('/jobs/{job}/save', [MarketplaceController::class, 'save'])->middleware('role:candidate')->name('jobs.save');
    Route::post('/job-alerts', [MarketplaceController::class, 'alert'])->middleware('role:candidate')->name('job-alerts.store');
    Route::post('/jobs/{job}/report', [MarketplaceController::class, 'report'])->name('jobs.report');
    Route::post('/jobs/{job}/track', [MarketplaceController::class, 'track'])->name('jobs.track');
    Route::post('/jobs/{job}/apply', [ApplicationController::class, 'store'])->middleware('role:candidate')->name('applications.store');
    Route::patch('/applications/{application}/withdraw', [ApplicationController::class, 'withdraw'])->middleware('role:candidate')->name('applications.withdraw');
    Route::middleware('role:employer,admin')->prefix('employer')->group(function () {
        Route::get('/', [EmployerWorkspaceController::class, 'index'])->name('employer.workspace');
        Route::get('/jobs/create', [EmployerWorkspaceController::class, 'create'])->name('employer.jobs.create');
        Route::get('/jobs/{job}/edit', [EmployerWorkspaceController::class, 'edit'])->name('employer.jobs.edit');
        Route::get('/jobs/{job}/applicants', [EmployerWorkspaceController::class, 'applicants'])->name('employer.jobs.applicants');
        Route::post('/jobs', [EmployerJobController::class, 'store'])->name('employer.jobs.store');
        Route::patch('/jobs/{job}', [EmployerJobController::class, 'update'])->name('employer.jobs.update');
        Route::delete('/jobs/{job}', [EmployerJobController::class, 'destroy'])->name('employer.jobs.destroy');
        Route::patch('/applications/{application}/status', [ApplicationController::class, 'updateStatus'])->name('employer.applications.status');
        Route::post('/applications/{application}/interviews', [InterviewController::class, 'store'])->name('employer.interviews.store');
        Route::post('/companies/{company}/plans/{plan}', [DummyBillingController::class, 'subscribe'])->name('dummy-billing.subscribe');
    });
    Route::middleware('role:admin')->prefix('admin')->group(function () {
        Route::get('/', [AdminController::class, 'index'])->name('admin.workspace');
        Route::patch('/jobs/{job}/moderate', [AdminController::class, 'moderateJob'])->name('admin.jobs.moderate');
        Route::patch('/jobs/{job}/feature', [AdminController::class, 'featureJob'])->name('admin.jobs.feature');
        Route::patch('/companies/{company}/verify', [AdminController::class, 'verifyCompany'])->name('admin.companies.verify');
        Route::patch('/companies/{company}/feature', [AdminController::class, 'featureCompany'])->name('admin.companies.feature');
        Route::patch('/users/{user}', [AdminController::class, 'updateUser'])->name('admin.users.update');
        Route::patch('/reports/{report}', [AdminController::class, 'resolveReport'])->name('admin.reports.update');
        Route::post('/categories', [AdminController::class, 'storeCategory'])->name('admin.categories.store');
        Route::delete('/categories/{category}', [AdminController::class, 'destroyCategory'])->name('admin.categories.destroy');
        Route::post('/skills', [AdminController::class, 'storeSkill'])->name('admin.skills.store');
        Route::delete('/skills/{skill}', [AdminController::class, 'destroySkill'])->name('admin.skills.destroy');
    });
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
