<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationPreference extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['in_app' => 'boolean', 'email' => 'boolean', 'application_updates' => 'boolean', 'job_alerts' => 'boolean', 'interview_reminders' => 'boolean'];
    }
}
