<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Resume extends Model
{
    protected $guarded = [];
    protected function casts(): array { return ['is_default' => 'boolean']; }
    public function user() { return $this->belongsTo(User::class); }
}
