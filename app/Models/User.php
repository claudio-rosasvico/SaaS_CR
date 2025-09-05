<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Models\Concerns\BelongsToOrganization;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, BelongsToOrganization;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'current_organization_id',
        'role'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function organizations()
    {
        return $this->belongsToMany(\App\Models\Organization::class)->withTimestamps()->withPivot('role');
    }
    public function currentOrganization()
    {
        return $this->belongsTo(\App\Models\Organization::class, 'current_organization_id');
    }
    public function isOwnerOrAdmin(?int $orgId = null): bool
    {
        $orgId ??= $this->current_organization_id;
        return $this->organizations()
            ->where('organization_id', $orgId)
            ->whereIn('role', ['owner', 'admin'])
            ->exists();
    }
}
