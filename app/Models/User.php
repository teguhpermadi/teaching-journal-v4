<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasUlids, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'last_login_at',
        'last_logout_at',
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
            'last_login_at' => 'datetime',
            'last_logout_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (User $user) {
            $user->assignRole('teacher');
        });
    }

    public function subjects()
    {
        return $this->hasMany(Subject::class);
    }

    public function socialiteUsers()
    {
        return $this->hasMany(SocialiteUser::class);
    }

    /**
     * Determine if user is currently online based on login/logout times
     */
    public function isOnline(): bool
    {
        // If never logged in, definitely not online
        if (!$this->last_login_at) {
            return false;
        }

        // If never logged out, check if login was recent (within 5 minutes)
        if (!$this->last_logout_at) {
            return $this->last_login_at->diffInMinutes(now()) <= 5;
        }

        // If logged out after last login, user is offline
        if ($this->last_logout_at->greaterThan($this->last_login_at)) {
            return false;
        }

        // If last login is more recent than logout, check if it's within 5 minutes
        return $this->last_login_at->diffInMinutes(now()) <= 5;
    }

    /**
     * Get user status string
     */
    public function getStatusAttribute(): string
    {
        if (!$this->last_login_at) {
            return 'Belum Login';
        }

        // If logged out after login, user is offline
        if ($this->last_logout_at && $this->last_logout_at->greaterThan($this->last_login_at)) {
            return 'Offline';
        }

        $diffInMinutes = $this->last_login_at->diffInMinutes(now());

        if ($diffInMinutes <= 5) {
            return 'Online';
        } elseif ($diffInMinutes <= 30) {
            return 'Baru Saja';
        } elseif ($this->last_login_at->isToday()) {
            return 'Hari Ini';
        } else {
            return 'Offline';
        }
    }
}
