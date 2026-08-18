<?php
namespace App\Http\Controllers;
use App\Models\CandidateProfile;
use App\Models\Company;
use App\Models\JobListing;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
class MarketplaceController extends Controller {
 public function profile(Request $r){$data=$r->validate(['headline'=>'nullable|max:160','bio'=>'nullable|max:5000','phone'=>'nullable|max:30','city'=>'nullable|max:100','country'=>'nullable|max:100','visibility'=>'required|in:public,employers,private']);CandidateProfile::updateOrCreate(['user_id'=>$r->user()->id],$data);return back()->with('success','Candidate profile updated.');}
 public function company(Request $r){$data=$r->validate(['name'=>'required|max:150','email'=>'nullable|email','website'=>'nullable|url','industry'=>'nullable|max:100','size'=>'nullable|max:50','city'=>'nullable|max:100','country'=>'nullable|max:100','description'=>'nullable|max:5000']);$data['owner_id']=$r->user()->id;$data['slug']=Str::slug($data['name']).'-'.Str::lower(Str::random(5));$company=Company::create($data);$company->members()->attach($r->user()->id,['role'=>'owner']);return back()->with('success','Company submitted for verification.');}
 public function save(Request $r,JobListing $job){$r->user()->belongsToMany(JobListing::class,'saved_jobs')->toggle($job->id);return back()->with('success','Saved jobs updated.');}
 public function alert(Request $r){$data=$r->validate(['name'=>'required|max:100','filters'=>'required|array','frequency'=>'required|in:daily,weekly']);DB::table('job_alerts')->insert($data+['user_id'=>$r->user()->id,'active'=>true,'created_at'=>now(),'updated_at'=>now()]);return back()->with('success','Job alert created.');}
 public function report(Request $r,JobListing $job){$data=$r->validate(['reason'=>'required|max:100','details'=>'nullable|max:2000']);DB::table('reports')->insert(['reporter_id'=>$r->user()->id,'reportable_type'=>JobListing::class,'reportable_id'=>$job->id,'reason'=>$data['reason'],'details'=>$data['details']??null,'status'=>'open','created_at'=>now(),'updated_at'=>now()]);return back()->with('success','Report submitted.');}
 public function track(Request $r,JobListing $job){$data=$r->validate(['event'=>'required|in:job_viewed,job_saved,application_started']);DB::table('analytics_events')->insert(['user_id'=>$r->user()?->id,'event'=>$data['event'],'subject_type'=>JobListing::class,'subject_id'=>$job->id,'properties'=>json_encode([]),'created_at'=>now(),'updated_at'=>now()]);return response()->noContent();}
}
