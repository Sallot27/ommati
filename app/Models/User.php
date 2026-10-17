<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use NotificationChannels\WebPush\HasPushSubscriptions;

#[Fillable(['name', 'email', 'password', 'is_admin', 'prefs'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    use HasFactory, HasPushSubscriptions, Notifiable;

    /** Personal preferences and their defaults. */
    public const PREF_DEFAULTS = [
        'reciter' => 'ar.husary',
        'hifz_daily_target' => 3,
        'last_read' => null,
        'location_lat' => null,
        'location_lng' => null,
        'location_name' => null,
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'prefs' => 'array',
        ];
    }

    /** Normalise a typed name so "أم  محمد " and "أم محمد" are the same person. */
    public static function normaliseName(string $name): string
    {
        $name = preg_replace('/[\x{064B}-\x{0652}\x{0640}]/u', '', $name); // harakat & tatweel
        $name = preg_replace('/\s+/u', ' ', trim($name));

        return Str::limit($name, 40, '');
    }

    /** Find or create the reader account for a name (admins never match). */
    public static function readerNamed(string $name): self
    {
        $name = self::normaliseName($name);
        $user = static::query()->where('is_admin', false)->where('name', $name)->first();

        return $user ?? static::query()->create([
            'name' => $name,
            'email' => 'reader-'.Str::uuid().'@app.local',
            'password' => Str::random(40),
            'is_admin' => false,
        ]);
    }

    public function pref(string $key): mixed
    {
        $value = $this->prefs[$key] ?? null;
        if ($value !== null) {
            return $value;
        }
        if (str_starts_with($key, 'location_')) {
            return Setting::get($key); // fall back to the app-wide default city
        }

        return self::PREF_DEFAULTS[$key] ?? null;
    }

    public function setPrefs(array $values): void
    {
        $this->prefs = array_merge($this->prefs ?? [], $values);
        $this->save();
    }

    public function memorized(): HasMany
    {
        return $this->hasMany(MemorizedAyah::class);
    }

    public function activity(): HasMany
    {
        return $this->hasMany(ActivityDay::class);
    }

    public function favoriteHadiths(): BelongsToMany
    {
        return $this->belongsToMany(Hadith::class, 'hadith_favorites')->withTimestamps();
    }

    public function today(): ActivityDay
    {
        return ActivityDay::query()->firstOrCreate(['user_id' => $this->id, 'date' => now()->toDateString()]);
    }
}
