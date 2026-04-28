<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vessel extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'fleet_id',
        'name',
        'imo',
        'vessel_type_id',
        'vessel_status_id',
        'device_token',
        'pending_command',
        'device_send_interval',
        'device_firmware_version',
        'device_last_seen_at',
        'device_ip',
        'device_uptime',
    ];

    protected $with = ['vesselType', 'vesselStatus'];

    protected $hidden = [
        'vessel_type_id',
        'vessel_status_id',
        'deleted_at',
        'device_token',  // No se expone en listados — solo en la vista de configuración
    ];

    protected $casts = [
        'created_at'           => 'datetime',
        'updated_at'           => 'datetime',
        'deleted_at'           => 'datetime',
        'device_last_seen_at'  => 'datetime',
        'device_send_interval' => 'integer',
        'device_uptime'        => 'integer',
    ];


    /**
     * Cada Vessel “pertenece a” un User.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    /**
     * Flota a la que pertenece esta embarcación (nullable).
     */
    public function fleet()
    {
        return $this->belongsTo(Fleet::class);
    }

    /**
     * Relación con el tipo de embarcación.
     */
    public function vesselType()
    {
        return $this->belongsTo(VesselType::class);
    }

    /**
     * Relación con el estado de embarcación.
     */
    public function vesselStatus()
    {
        return $this->belongsTo(VesselStatus::class);
    }

    /**
     * Un Vessel puede tener muchos Trackings.
     */
    public function trackings()
    {
        return $this->hasMany(Tracking::class);
    }

    /**
     * Un Vessel puede tener muchas VesselMetrics.
     */
    public function metrics()
    {
        return $this->hasMany(VesselMetric::class);
    }

    /**
     * Telemetría IoT del dispositivo.
     */
    public function telemetry()
    {
        return $this->hasMany(VesselTelemetry::class);
    }

    /**
     * Genera y persiste un nuevo device_token seguro (32 bytes → 64 hex chars).
     * Retorna el token en claro para mostrarlo una sola vez al usuario.
     */
    public function rotateDeviceToken(): string
    {
        $token = bin2hex(random_bytes(32));
        $this->update(['device_token' => $token]);
        return $token;
    }

    /**
     * Encola un comando para que el dispositivo lo recoja en el próximo ping.
     * Comandos soportados: 'reboot', 'update_firmware'
     */
    public function queueCommand(string $command): void
    {
        $this->update(['pending_command' => $command]);
    }

    /**
     * El dispositivo consume y limpia el comando pendiente.
     */
    public function consumeCommand(): ?string
    {
        $command = $this->pending_command;
        if ($command) {
            $this->update(['pending_command' => null]);
        }
        return $command;
    }

    /**
     * Registra actividad del dispositivo (cada ping).
     */
    public function recordHeartbeat(array $deviceInfo = []): void
    {
        $data = ['device_last_seen_at' => now()];

        if (isset($deviceInfo['firmware'])) {
            $data['device_firmware_version'] = $deviceInfo['firmware'];
        }
        if (isset($deviceInfo['ip'])) {
            $data['device_ip'] = $deviceInfo['ip'];
        }
        if (isset($deviceInfo['uptime'])) {
            $data['device_uptime'] = $deviceInfo['uptime'];
        }

        $this->update($data);
    }

    /**
     * Retorna la configuración que se envía al dispositivo en cada ping.
     */
    public function getDeviceConfig(): array
    {
        return [
            'send_interval' => $this->device_send_interval,
        ];
    }

    /**
     * ¿El dispositivo está online? (último ping < 3x intervalo)
     */
    public function isDeviceOnline(): bool
    {
        if (! $this->device_last_seen_at) {
            return false;
        }

        $threshold = max($this->device_send_interval * 3, 60);
        return $this->device_last_seen_at->diffInSeconds(now()) < $threshold;
    }
}
