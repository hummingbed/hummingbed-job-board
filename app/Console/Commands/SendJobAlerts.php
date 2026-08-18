<?php

namespace App\Console\Commands;

use App\Models\JobListing;
use App\Models\User;
use App\Notifications\JobAlertMatchesNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SendJobAlerts extends Command
{
    protected $signature = 'job-alerts:send';

    protected $description = 'Deliver new jobs matching active candidate alerts';

    public function handle(): int
    {
        DB::table('job_alerts')->where('active', true)->orderBy('id')->chunkById(100, function ($alerts) {
            foreach ($alerts as $alert) {
                if ($alert->frequency === 'weekly' && ! now()->isMonday()) {
                    continue;
                }$filters = json_decode($alert->filters, true) ?: [];
                $jobs = JobListing::published()->with('company:id,name')->whereNotExists(fn ($q) => $q->selectRaw(1)->from('job_alert_deliveries')->whereColumn('job_alert_deliveries.job_listing_id', 'job_listings.id')->where('job_alert_deliveries.job_alert_id', $alert->id))->when($filters['keyword'] ?? null, fn ($q, $v) => $q->where(fn ($x) => $x->where('title', 'like', "%$v%")->orWhere('description', 'like', "%$v%")))->when($filters['location'] ?? null, fn ($q, $v) => $q->where(fn ($x) => $x->where('city', 'like', "%$v%")->orWhere('country', 'like', "%$v%")))->when($filters['category'] ?? null, fn ($q, $v) => $q->where('job_category_id', $v))->latest('published_at')->limit(10)->get();
                if ($jobs->isEmpty()) {
                    continue;
                }foreach ($jobs as $job) {
                    DB::table('job_alert_deliveries')->insertOrIgnore(['job_alert_id' => $alert->id, 'job_listing_id' => $job->id, 'delivered_at' => now()]);
                }$alert->filters = $filters;
                User::find($alert->user_id)?->notify(new JobAlertMatchesNotification($alert, $jobs->map->only(['id', 'title', 'slug'])->all()));
            }
        });
        $this->info('Job alerts delivered.');

        return self::SUCCESS;
    }
}
