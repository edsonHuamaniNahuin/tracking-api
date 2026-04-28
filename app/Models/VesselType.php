<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VesselType extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'slug', 'category'];



    protected $hidden = [
        'slug',
    ];
    /**
     * Relación inversa: un tipo puede tener muchas embarcaciones
     */
    public function vessels()
    {
        return $this->hasMany(Vessel::class);
    }
}
