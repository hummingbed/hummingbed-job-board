<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class VercelCronController extends Controller
{
    private function authorizeCron(Request $request): void
    {
        $secret = config('services.vercel.cron_secret');
        abort_unless($secret && hash_equals('Bearer '.$secret, (string) $request->header('Authorization')), 401);
    }

    public function jobAlerts(Request $request)
    {
        $this->authorizeCron($request);
        Artisan::call('job-alerts:send');
        return response()->json(['ok' => true, 'output' => trim(Artisan::output())]);
    }

    public function interviewReminders(Request $request)
    {
        $this->authorizeCron($request);
        Artisan::call('interviews:send-reminders');
        return response()->json(['ok' => true, 'output' => trim(Artisan::output())]);
    }
}
