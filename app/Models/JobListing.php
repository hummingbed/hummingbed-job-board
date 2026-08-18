<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
class JobListing extends Model {
 protected $guarded=[]; protected function casts():array{return ['published_at'=>'datetime','closed_at'=>'datetime','application_deadline'=>'date','is_featured'=>'boolean','salary_visible'=>'boolean'];}
 public function company(){return $this->belongsTo(Company::class);} public function category(){return $this->belongsTo(JobCategory::class,'job_category_id');} public function creator(){return $this->belongsTo(User::class,'created_by');} public function applications(){return $this->hasMany(Application::class);} public function skills(){return $this->belongsToMany(Skill::class);}
 public function scopePublished(Builder $q):Builder{return $q->where('status','published')->where(fn($x)=>$x->whereNull('application_deadline')->orWhereDate('application_deadline','>=',today()));}
}
