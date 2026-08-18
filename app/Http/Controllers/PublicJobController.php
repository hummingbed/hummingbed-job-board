<?php
namespace App\Http\Controllers;
use App\Models\Company;
use App\Models\JobCategory;
use App\Models\JobListing;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
class PublicJobController extends Controller {
 public function index(Request $r){
  $jobs=JobListing::query()->published()->with(['company:id,name,logo_path','category:id,name'])
   ->when($r->string('q')->toString(),fn($q,$v)=>$q->where(fn($x)=>$x->where('title','like',"%$v%")->orWhere('description','like',"%$v%")->orWhereHas('company',fn($c)=>$c->where('name','like',"%$v%"))))
   ->when($r->string('location')->toString(),fn($q,$v)=>$q->where(fn($x)=>$x->where('city',$v)->orWhere('country',$v)))
   ->when($r->integer('category'),fn($q,$v)=>$q->where('job_category_id',$v))
   ->when($r->input('employment_type'),fn($q,$v)=>$q->whereIn('employment_type',(array)$v))
   ->when($r->input('workplace_type'),fn($q,$v)=>$q->whereIn('workplace_type',(array)$v))
   ->when($r->string('experience')->toString(),fn($q,$v)=>$q->where('experience_level',$v))
   ->when($r->integer('salary_min'),fn($q,$v)=>$q->where('salary_max','>=',$v))
   ->when($r->string('posted')->toString(),function($q,$v){$days=['day'=>1,'week'=>7,'month'=>30][$v]??null;if($days)$q->where('published_at','>=',now()->subDays($days));})
   ->when($r->input('sort')==='salary',fn($q)=>$q->orderByDesc('salary_max'),fn($q)=>$q->latest('published_at'))->paginate(6)->withQueryString();
  $u=$r->user();return Inertia::render('Jobs',['canLogin'=>Route::has('login'),'canRegister'=>Route::has('register'),'databaseJobs'=>$jobs,
   'categories'=>JobCategory::withCount(['jobs'=>fn($q)=>$q->published()])->orderBy('name')->get(),
   'locations'=>JobListing::published()->whereNotNull('city')->distinct()->orderBy('city')->pluck('city'),
   'topCompanies'=>Company::withCount(['jobs'=>fn($q)=>$q->published()])->where('verification_status','verified')->orderByDesc('is_featured')->limit(4)->get(),
   'filters'=>$r->only(['q','location','category','employment_type','workplace_type','experience','salary_min','posted','sort']),
   'savedJobIds'=>$u?->role==='candidate'?$u->belongsToMany(JobListing::class,'saved_jobs')->pluck('job_listings.id'):[]]);
 }
 public function show(Request $r,JobListing $job){abort_unless($job->status==='published',404);$job->load(['company','category','skills'])->loadCount('applications');$related=JobListing::published()->with(['company:id,name','category:id,name'])->whereKeyNot($job->id)->when($job->job_category_id,fn($q)=>$q->where('job_category_id',$job->job_category_id))->limit(3)->get();$u=$r->user();return Inertia::render('JobDetails',['canLogin'=>Route::has('login'),'canRegister'=>Route::has('register'),'job'=>$job,'relatedJobs'=>$related,'isSaved'=>$u?->role==='candidate'&&$u->belongsToMany(JobListing::class,'saved_jobs')->whereKey($job->id)->exists(),'application'=>$u?->role==='candidate'?$u->applications()->where('job_listing_id',$job->id)->first():null,'resumes'=>$u?->role==='candidate'?$u->resumes()->latest()->get(['id','name','is_default']):[]]);}
}
