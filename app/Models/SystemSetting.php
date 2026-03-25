<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SystemSetting extends Model
{
    protected $fillable = ['key', 'value', 'type', 'group', 'label', 'description'];

    /**
     * Obtiene el valor de una configuración por su clave.
     * Usa caché para evitar consultas repetidas.
     */
    public static function getValue(string $key, mixed $default = null): mixed
    {
        return Cache::remember("system_setting.{$key}", 3600, function () use ($key, $default) {
            $setting = static::where('key', $key)->first();
            return $setting ? static::castValue($setting->value, $setting->type) : $default;
        });
    }

    /**
     * Establece el valor de una configuración.
     */
    public static function setValue(string $key, mixed $value): void
    {
        $setting = static::where('key', $key)->first();
        if ($setting) {
            $setting->update(['value' => is_array($value) ? json_encode($value) : (string) $value]);
        }
        Cache::forget("system_setting.{$key}");
    }

    /**
     * Retorna la zona horaria configurada del sistema.
     */
    public static function getTimezone(): string
    {
        return static::getValue('timezone', 'America/Lima');
    }

    /**
     * Cast del valor según su tipo declarado.
     */
    public static function castValue(string $value, string $type): mixed
    {
        return match ($type) {
            'integer' => (int) $value,
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'json'    => json_decode($value, true),
            default   => $value,
        };
    }
}
