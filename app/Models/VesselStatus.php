<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VesselStatus extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'slug'];


    protected $hidden = [
        'created_at',
        'updated_at',
        'slug'
    ];
    /**
     * Un status puede aplicarse a muchos vessels
     */
    public function vessels()
    {
        return $this->hasMany(Vessel::class);
    }
}
