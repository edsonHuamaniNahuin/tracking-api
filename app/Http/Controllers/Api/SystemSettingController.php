<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;

class SystemSettingController extends Controller
{
    /**
     * GET /v1/settings
     * Retorna todas las configuraciones del sistema agrupadas.
     */
    public function index(): JsonResponse
    {
        $settings = SystemSetting::all()->groupBy('group')->map(function ($group) {
            return $group->map(fn ($s) => [
                'key'         => $s->key,
                'value'       => SystemSetting::castValue($s->value, $s->type),
                'type'        => $s->type,
                'label'       => $s->label,
                'description' => $s->description,
            ]);
        });

        return response()->json([
            'status'  => 200,
            'message' => 'OK',
            'data'    => $settings,
        ]);
    }

    /**
     * GET /v1/settings/{key}
     * Retorna una configuración individual.
     */
    public function show(string $key): JsonResponse
    {
        $setting = SystemSetting::where('key', $key)->first();

        if (! $setting) {
            return response()->json([
                'status'  => 404,
                'message' => "Configuración '{$key}' no encontrada",
                'data'    => null,
            ], 404);
        }

        return response()->json([
            'status'  => 200,
            'message' => 'OK',
            'data'    => [
                'key'         => $setting->key,
                'value'       => SystemSetting::castValue($setting->value, $setting->type),
                'type'        => $setting->type,
                'group'       => $setting->group,
                'label'       => $setting->label,
                'description' => $setting->description,
            ],
        ]);
    }

    /**
     * PUT /v1/settings/{key}
     * Actualiza el valor de una configuración.
     */
    public function update(Request $request, string $key): JsonResponse
    {
        $setting = SystemSetting::where('key', $key)->first();

        if (! $setting) {
            return response()->json([
                'status'  => 404,
                'message' => "Configuración '{$key}' no encontrada",
                'data'    => null,
            ], 404);
        }

        // Validación específica según la clave
        $rules = $this->getValidationRules($key, $setting->type);
        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 422,
                'message' => 'Datos inválidos',
                'data'    => $validator->errors(),
            ], 422);
        }

        $value = $request->input('value');
        $setting->update(['value' => is_array($value) ? json_encode($value) : (string) $value]);
        Cache::forget("system_setting.{$key}");

        return response()->json([
            'status'  => 200,
            'message' => 'Configuración actualizada',
            'data'    => [
                'key'   => $setting->key,
                'value' => SystemSetting::castValue($setting->value, $setting->type),
            ],
        ]);
    }

    /**
     * PUT /v1/settings (batch update)
     * Actualiza múltiples configuraciones a la vez.
     */
    public function batchUpdate(Request $request): JsonResponse
    {
        $request->validate([
            'settings'        => 'required|array',
            'settings.*.key'  => 'required|string',
            'settings.*.value'=> 'required',
        ]);

        $updated = [];
        foreach ($request->input('settings') as $item) {
            $setting = SystemSetting::where('key', $item['key'])->first();
            if ($setting) {
                $value = $item['value'];
                $setting->update(['value' => is_array($value) ? json_encode($value) : (string) $value]);
                Cache::forget("system_setting.{$item['key']}");
                $updated[] = $item['key'];
            }
        }

        return response()->json([
            'status'  => 200,
            'message' => count($updated) . ' configuraciones actualizadas',
            'data'    => ['updated_keys' => $updated],
        ]);
    }

    /**
     * Reglas de validación por clave.
     */
    private function getValidationRules(string $key, string $type): array
    {
        return match ($key) {
            'timezone' => ['value' => 'required|string|timezone'],
            default    => match ($type) {
                'integer' => ['value' => 'required|integer'],
                'boolean' => ['value' => 'required|boolean'],
                'json'    => ['value' => 'required|array'],
                default   => ['value' => 'required|string|max:1000'],
            },
        };
    }
}
