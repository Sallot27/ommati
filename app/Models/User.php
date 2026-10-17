<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use NotificationChannels\WebPush\HasPushSubscriptions;

#[Fillable(['name', 'email', 'password', 'is_admin'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    use HasFactory, HasPushSubscriptions, Notifiable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
        ];
    }

    /**
     * The single "reader" account that owns the app's push subscriptions.
     * The app has no login for the reader; devices subscribe on her behalf.
     */
    public static function reader(): self
    {
        return static::firstOrCreate(
            ['email' => 'reader@app.local'],
            ['name' => 'reader', 'password' => bin2hex(random_bytes(24)), 'is_admin' => false],
        );
    }
}
