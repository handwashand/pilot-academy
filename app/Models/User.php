<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

#[Fillable(['name', 'certificate_name', 'email', 'password', 'company_id', 'role', 'login_token'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /** Runs the platform: every course, every partner, every setting. */
    public const ROLE_ADMIN = 'admin';

    /** Owns the training for their assigned products, and nothing else. */
    public const ROLE_CREATOR = 'creator';

    /** A partner who takes courses. The default for a new account. */
    public const ROLE_LEARNER = 'learner';

    public const ROLE_LABELS = [
        self::ROLE_ADMIN => 'Admin',
        self::ROLE_CREATOR => 'Creator',
        self::ROLE_LEARNER => 'Learner',
    ];

    /** New accounts are partners until an admin says otherwise. */
    protected $attributes = [
        'role' => self::ROLE_LEARNER,
    ];

    /**
     * Admins and creators use the Filament panel; what they can do inside it
     * is decided per resource by the policies. Learners never get in.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->isAdmin() || $this->isCreator();
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isCreator(): bool
    {
        return $this->role === self::ROLE_CREATOR;
    }

    public function isLearner(): bool
    {
        return $this->role === self::ROLE_LEARNER;
    }

    public function roleLabel(): string
    {
        return self::ROLE_LABELS[$this->role] ?? $this->role;
    }

    /** Everyone who takes courses — the only people who belong in reports. */
    public function scopeLearners(Builder $query): Builder
    {
        return $query->where($this->qualifyColumn('role'), self::ROLE_LEARNER);
    }

    /** Products this creator owns the training for. */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class)->withTimestamps()->orderBy('name');
    }

    /**
     * May this user create and edit the training for a product? Admins own
     * everything; a creator only owns the products assigned to them.
     */
    public function ownsProduct(?Product $product): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        if (! $this->isCreator() || ! $product) {
            return false;
        }

        return $this->products()->whereKey($product->getKey())->exists();
    }

    /**
     * May this user manage a course? Admins may manage every course, including
     * ones with no product yet; a creator only their own products' courses.
     */
    public function canManageCourse(?Course $course): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        if (! $this->isCreator() || ! $course) {
            return false;
        }

        return $course->product_id !== null
            && $this->products()->whereKey($course->product_id)->exists();
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

    public function quizAttempts(): HasMany
    {
        return $this->hasMany(QuizAttempt::class)->latest();
    }

    public function certificates(): HasMany
    {
        return $this->hasMany(Certificate::class)->latest('issued_at');
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
            'last_login_at' => 'datetime',
        ];
    }
}
