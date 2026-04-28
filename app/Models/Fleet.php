<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Fleet extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'name',
        'description',
        'color',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /** Propietario de la flota. */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Embarcaciones que pertenecen a esta flota. */
    public function vessels(): HasMany
    {
        return $this->hasMany(Vessel::class);
    }
}
