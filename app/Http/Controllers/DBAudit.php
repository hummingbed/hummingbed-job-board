<?php

namespace App\Http\Controllers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DBAudit
{
    public static function write(Request $r, string $action, Model $model, array $metadata = []): void
    {
        DB::table('audit_logs')->insert(['user_id' => $r->user()?->id, 'action' => $action, 'auditable_type' => $model::class, 'auditable_id' => $model->getKey(), 'metadata' => json_encode($metadata), 'ip_address' => $r->ip(), 'created_at' => now(), 'updated_at' => now()]);
    }
}
