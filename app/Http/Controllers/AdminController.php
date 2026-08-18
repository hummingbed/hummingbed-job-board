<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\Company;
use App\Models\JobCategory;
use App\Models\JobListing;
use App\Models\Skill;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;

class AdminController extends Controller
{
    public function index(Request $r)
    {
        $users = User::query()->when($r->string('q')->toString(), fn ($q, $v) => $q->where(fn ($x) => $x->where('name', 'like', "%$v%")->orWhere('email', 'like', "%$v%")))->latest()->limit(50)->get(['id', 'name', 'email', 'role', 'status', 'created_at']);

        return Inertia::render('Admin/Workspace', [
            'metrics' => ['Users' => User::count(), 'Companies' => Company::count(), 'Published jobs' => JobListing::where('status', 'published')->count(), 'Applications' => Application::count()],
            'pendingJobs' => JobListing::with(['company:id,name', 'creator:id,name,email'])->where('status', 'pending')->latest()->get(),
            'publishedJobs' => JobListing::with('company:id,name')->where('status', 'published')->latest('published_at')->limit(30)->get(),
            'companies' => Company::withCount('jobs')->orderByRaw("case when verification_status = 'pending' then 0 else 1 end")->latest()->limit(50)->get(),
            'users' => $users, 'userFilters' => $r->only('q'),
            'reports' => DB::table('reports')->leftJoin('users', 'users.id', '=', 'reports.reporter_id')->latest('reports.created_at')->limit(50)->get(['reports.*', 'users.name as reporter_name']),
            'subscriptions' => Subscription::with(['company:id,name', 'plan:id,name,price_cents'])->latest()->limit(50)->get(),
            'categories' => JobCategory::withCount('jobs')->orderBy('name')->get(), 'skills' => Skill::orderBy('name')->get(),
            'auditLogs' => DB::table('audit_logs')->leftJoin('users', 'users.id', '=', 'audit_logs.user_id')->latest('audit_logs.created_at')->limit(100)->get(['audit_logs.*', 'users.name as user_name']),
        ]);
    }

    public function moderateJob(Request $r, JobListing $job)
    {
        $data = $r->validate(['status' => 'required|in:published,rejected', 'note' => 'nullable|max:1000']);
        $job->update(['status' => $data['status'], 'published_at' => $data['status'] === 'published' ? now() : null]);
        DBAudit::write($r, 'job.'.$data['status'], $job, ['note' => $data['note'] ?? null]);

        return back()->with('success', 'Moderation completed.');
    }

    public function verifyCompany(Request $r, Company $company)
    {
        $data = $r->validate(['status' => 'required|in:verified,rejected']);
        $company->update(['verification_status' => $data['status']]);
        DBAudit::write($r, 'company.'.$data['status'], $company);

        return back()->with('success', 'Company review completed.');
    }

    public function updateUser(Request $r, User $user)
    {
        $data = $r->validate(['status' => 'sometimes|in:active,suspended', 'role' => 'sometimes|in:candidate,employer,admin']);
        abort_if($user->is($r->user()) && (($data['status'] ?? 'active') === 'suspended' || ($data['role'] ?? 'admin') !== 'admin'), 422, 'You cannot remove your own admin access.');
        if (array_key_exists('status', $data)) {
            $data['suspended_at'] = $data['status'] === 'suspended' ? now() : null;
        }$user->update($data);
        DBAudit::write($r, 'user.updated', $user, $data);

        return back()->with('success', 'User updated.');
    }

    public function resolveReport(Request $r, int $report)
    {
        $data = $r->validate(['status' => 'required|in:reviewing,resolved,dismissed']);
        $row = DB::table('reports')->where('id', $report)->first();
        abort_unless($row, 404);
        DB::table('reports')->where('id', $report)->update(['status' => $data['status'], 'resolved_by' => in_array($data['status'], ['resolved', 'dismissed']) ? $r->user()->id : null, 'updated_at' => now()]);
        DBAudit::write($r, 'report.'.$data['status'], JobListing::find($row->reportable_id) ?? $r->user());

        return back()->with('success', 'Report updated.');
    }

    public function featureJob(Request $r, JobListing $job)
    {
        $job->update(['is_featured' => ! $job->is_featured]);
        DBAudit::write($r, 'job.featured', $job, ['featured' => $job->is_featured]);

        return back()->with('success', 'Featured jobs updated.');
    }

    public function featureCompany(Request $r, Company $company)
    {
        $company->update(['is_featured' => ! $company->is_featured]);
        DBAudit::write($r, 'company.featured', $company, ['featured' => $company->is_featured]);

        return back()->with('success', 'Featured companies updated.');
    }

    public function storeCategory(Request $r)
    {
        $data = $r->validate(['name' => 'required|max:100|unique:job_categories,name']);
        $category = JobCategory::create(['name' => $data['name'], 'slug' => Str::slug($data['name']).'-'.Str::lower(Str::random(4))]);
        DBAudit::write($r, 'category.created', $category);

        return back()->with('success', 'Category created.');
    }

    public function destroyCategory(Request $r, JobCategory $category)
    {
        abort_if($category->jobs()->exists(), 422, 'Categories with jobs cannot be deleted.');
        DBAudit::write($r, 'category.deleted', $category);
        $category->delete();

        return back()->with('success', 'Category deleted.');
    }

    public function storeSkill(Request $r)
    {
        $data = $r->validate(['name' => 'required|max:100|unique:skills,name']);
        $skill = Skill::create(['name' => $data['name'], 'slug' => Str::slug($data['name']).'-'.Str::lower(Str::random(4))]);
        DBAudit::write($r, 'skill.created', $skill);

        return back()->with('success','Skill created.');
    }

    public function destroySkill(Request $r,Skill $skill)
    {
        DBAudit::write($r,'skill.deleted',$skill);
        $skill->delete();

        return back()->with('success','Skill deleted.');
    }
}
