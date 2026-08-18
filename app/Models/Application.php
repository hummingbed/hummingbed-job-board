<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Application extends Model { protected $guarded=[]; protected function casts():array{return ['submitted_at'=>'datetime','withdrawn_at'=>'datetime'];} public function jobListing(){return $this->belongsTo(JobListing::class);} public function candidate(){return $this->belongsTo(User::class,'candidate_id');} public function history(){return $this->hasMany(ApplicationStatusHistory::class);} }
