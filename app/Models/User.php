<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use Notifiable;
    use HasFactory;
    use HasRoles;

    /**
     * Especifica el guard por defecto para Spatie Permission
     */
    protected $guard_name = 'api';

    // Campos rellenables
    protected $fillable = [
        'name',
        'email',
        'password',
        'username',
        'photo_url',
        'notifications_count',
        'newsletter_subscribed',
        'public_profile',
        'show_online_status',
        'phone',
        'bio',
        'location',
        'two_factor_enabled',
        'email_notifications_enabled',
        'push_notifications_enabled',
    ];

    // Campos ocultos (no se exponen en JSON)
    protected $hidden = [
        'password',
        'remember_token',

    ];


    protected $casts = [
        'notifications_count'   => 'integer',
        'newsletter_subscribed' => 'boolean',
        'public_profile'        => 'boolean',
        'show_online_status'    => 'boolean',
        'two_factor_enabled'    => 'boolean',
        'email_notifications_enabled'   => 'boolean',
        'push_notifications_enabled'    => 'boolean',
    ];

    /**
     * Devuelve el identificador que se pondrá en el token (normalmente el PK).
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Devuelve un array con claims personalizados que quieras agregar.
     */
    public function getJWTCustomClaims(): array
    {
        return [];
    }

    protected $appends = [
        'photoUrl',
        'avatar',
    ];


    public function getPhotoUrlAttribute(): ?string
    {
        $photo = $this->attributes['photo_url'] ?? null;
        return $photo ? url("storage/{$photo}") : null;
    }
    public function getTwoFactorEnabledAttribute(): bool
    {
        return (bool) ($this->attributes['two_factor_enabled'] ?? false);
    }

    public function getAvatarAttribute(): string
    {
        return $this->photoUrl
            ?? 'https://ui-avatars.com/api/?name=' . urlencode($this->name);
    }

    /**
     * Un User puede tener muchos Vessels.
     */
    public function vessels()
    {
        return $this->hasMany(Vessel::class);
    }
}
