<?php
namespace App\Http\Controllers;
use App\Models\Company;
use App\Models\Plan;
use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
class DummyBillingController extends Controller
{
    public function subscribe(Request $r, Company $company, Plan $plan)
    {
        abort_unless($company->owner_id === $r->user()->id, 403);
        Subscription::where('company_id', $company->id)->where('status', 'active')->update(['status' => 'cancelled', 'ends_at' => now()]);
        Subscription::create(['company_id' => $company->id, 'plan_id' => $plan->id, 'status' => 'active', 'dummy_reference' => 'DUMMY-' . Str::upper(Str::random(14)), 'starts_at' => now(), 'ends_at' => now()->addMonth()]);
        return back()->with('success', 'Dummy subscription activated.');
    }
}
