<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Notifications\InterviewReminderNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SendInterviewReminders extends Command
{
    protected $signature = 'interviews:send-reminders';

    protected $description = 'Send reminders for interviews scheduled in the next 24 hours';

    public function handle(): int
    {
        $rows = DB::table('interviews')->join('applications', 'applications.id', '=', 'interviews.application_id')->join('job_listings', 'job_listings.id', '=', 'applications.job_listing_id')->whereNull('interviews.reminder_sent_at')->where('interviews.status', 'scheduled')->whereBetween('interviews.scheduled_at', [now(), now()->addDay()])->get(['interviews.*', 'applications.candidate_id', 'job_listings.title as job_title']);
        foreach ($rows as $row) {
            User::find($row->candidate_id)?->notify(new InterviewReminderNotification($row));
            DB::table('interviews')->where('id', $row->id)->update(['reminder_sent_at' => now(), 'updated_at' => now()]);
        }$this->info($rows->count().' interview reminders sent.');

        return self::SUCCESS;
    }
}
