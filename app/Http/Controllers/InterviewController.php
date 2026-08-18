<?php
namespace App\Http\Controllers;
use App\Models\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class InterviewController extends Controller { public function store(Request $r,Application $application){abort_unless($application->jobListing->created_by===$r->user()->id||$r->user()->isAdmin(),403);$data=$r->validate(['scheduled_at'=>'required|date|after:now','type'=>'required|in:video,phone,onsite','location_or_url'=>'nullable|max:500','notes'=>'nullable|max:2000']);DB::transaction(function()use($data,$application,$r){DB::table('interviews')->insert($data+['application_id'=>$application->id,'status'=>'scheduled','created_at'=>now(),'updated_at'=>now()]);$application->update(['status'=>'interview']);DB::table('application_status_history')->insert(['application_id'=>$application->id,'changed_by'=>$r->user()->id,'from_status'=>'shortlisted','to_status'=>'interview','note'=>'Interview scheduled','created_at'=>now(),'updated_at'=>now()]);});return back()->with('success','Interview scheduled.');} }
