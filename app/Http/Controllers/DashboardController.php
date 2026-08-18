<?php
namespace App\Http\Controllers;
use App\Models\Application;
use App\Models\Company;
use App\Models\JobListing;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
class DashboardController extends Controller {
 public function __invoke(Request $request){$u=$request->user();$stats=match($u->role){
  'admin'=>['Users'=>User::count(),'Companies'=>Company::count(),'Pending jobs'=>JobListing::where('status','pending')->count(),'Applications'=>Application::count()],
  'employer'=>['Companies'=>$u->ownedCompanies()->count(),'Job listings'=>JobListing::where('created_by',$u->id)->count(),'Applications'=>Application::whereHas('jobListing',fn($q)=>$q->where('created_by',$u->id))->count(),'Pending review'=>JobListing::where('created_by',$u->id)->where('status','pending')->count()],
  default=>['Applications'=>$u->applications()->count(),'Saved jobs'=>$u->belongsToMany(JobListing::class,'saved_jobs')->count(),'Interviews'=>Application::where('candidate_id',$u->id)->where('status','interview')->count(),'Profile'=>$u->candidateProfile?'Complete':'Incomplete']};
  if ($u->role === 'candidate') return redirect()->route('candidate.workspace');
  if ($u->role === 'admin') return redirect()->route('admin.workspace');
  return Inertia::render('Dashboard',['role'=>$u->role,'stats'=>$stats]); }
}
