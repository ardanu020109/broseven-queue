<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'profile_photo',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function calledQueues(): HasMany
    {
        return $this->hasMany(Queue::class, 'called_by');
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /** URL foto profil, atau null jika belum diunggah. */
    protected function profilePhotoUrl(): Attribute
    {
        return Attribute::get(
            fn () => $this->profile_photo ? asset('storage/' . $this->profile_photo) : null
        );
    }

    /** Inisial nama untuk avatar pengganti foto. */
    protected function initials(): Attribute
    {
        return Attribute::get(function () {
            $words = preg_split('/\s+/', trim((string) $this->name)) ?: [];
            $letters = collect($words)->filter()->take(2)->map(fn ($w) => Str::upper(Str::substr($w, 0, 1)));

            return $letters->implode('') ?: 'A';
        });
    }
}
