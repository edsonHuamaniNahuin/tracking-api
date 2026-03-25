<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VesselMetric extends Model
{
   use HasFactory;

    protected $fillable = [
        'vessel_id',
        'period',
        'avg_speed',
        'fuel_consumption',
        'maintenance_count',
        'safety_incidents',
    ];

    protected $casts = [
        'avg_speed' => 'decimal:2',
        'fuel_consumption' => 'decimal:2',
        'maintenance_count' => 'integer',
        'safety_incidents' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function vessel()
    {
        return $this->belongsTo(Vessel::class);
    }
}
