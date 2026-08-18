<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $attributes = ['role' => 'candidate', 'status' => 'active'];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'status',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'suspended_at' => 'datetime',
        ];
    }

    public function candidateProfile(): HasOne { return $this->hasOne(CandidateProfile::class); }
    public function ownedCompanies(): HasMany { return $this->hasMany(Company::class, 'owner_id'); }
    public function applications(): HasMany { return $this->hasMany(Application::class, 'candidate_id'); }
    public function resumes(): HasMany { return $this->hasMany(Resume::class); }
    public function isAdmin(): bool { return $this->role === 'admin'; }
    public function isEmployer(): bool { return in_array($this->role, ['employer','admin'], true); }
}
