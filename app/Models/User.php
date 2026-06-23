<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

#[Fillable(['name', 'email', 'password', 'company_id', 'is_admin', 'login_token'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Only admins may access the Filament admin panel.
     * Student (partner) accounts use the public site only.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return (bool) $this->is_admin;
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /** Lessons this user has completed (quiz passed). */
    public function completedLessons(): BelongsToMany
    {
        return $this->belongsToMany(Lesson::class)->withPivot('completed_at')->withTimestamps();
    }

    public function activities(): HasMany
    {
        return $this->hasMany(ActivityEvent::class)->latest();
    }

    /** Stamp the last login time and log a login activity event. */
    public function recordLogin(): void
    {
        $this->forceFill(['last_login_at' => now()])->save();
        ActivityEvent::record($this, ActivityEvent::TYPE_LOGIN);
    }

    /** Ensure the user has a unique passwordless access token and return it. */
    public function ensureLoginToken(): string
    {
        if (! $this->login_token) {
            $this->forceFill(['login_token' => Str::random(48)])->save();
        }

        return $this->login_token;
    }

    /** Personal passwordless access (magic) link. */
    public function accessUrl(): string
    {
        return route('academy.enter', $this->ensureLoginToken());
    }

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
            'is_admin' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }
}
