<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * @OA\Tag(
 *   name="Roles & Permissions",
 *   description="Gestion de roles, permisos y asignacion de roles a usuarios"
 * )
 */
class RoleManagementController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    /**
     * @OA\Get(
     *   path="/api/v1/roles",
     *   tags={"Roles & Permissions"},
     *   summary="Listar roles con sus permisos",
     *   security={{"bearerAuth":{}}},
     *   @OA\Response(
     *     response=200,
     *     description="Lista de roles",
     *     @OA\JsonContent(
     *       @OA\Property(property="status", type="integer", example=200),
     *       @OA\Property(
     *         property="data",
     *         type="array",
     *         @OA\Items(
     *           @OA\Property(property="id",   type="integer", example=1),
     *           @OA\Property(property="name", type="string",  example="Administrator"),
     *           @OA\Property(
     *             property="permissions",
     *             type="array",
     *             @OA\Items(
     *               @OA\Property(property="name",  type="string", example="manage_users"),
     *               @OA\Property(property="label", type="string", example="Gestionar usuarios")
     *             )
     *           )
     *         )
     *       )
     *     )
     *   ),
     *   @OA\Response(response=403, description="Sin permiso")
     * )
     */
    public function roles(): JsonResponse
    {
        abort_unless(auth()->user()?->can('manage_roles'), 403, 'Sin permiso.');

        $roles = Role::with('permissions')
            ->where('guard_name', 'api')
            ->get()
            ->map(fn(Role $r) => [
                'id'          => $r->id,
                'name'        => $r->name,
                'permissions' => $r->permissions->map(fn($p) => [
                    'name'  => $p->name,
                    'label' => $p->label ?? $p->name,
                ])->values(),
            ]);

        return response()->json(['status' => 200, 'data' => $roles]);
    }

    /**
     * @OA\Get(
     *   path="/api/v1/permissions",
     *   tags={"Roles & Permissions"},
     *   summary="Listar todos los permisos disponibles",
     *   security={{"bearerAuth":{}}},
     *   @OA\Response(
     *     response=200,
     *     description="Lista de permisos",
     *     @OA\JsonContent(
     *       @OA\Property(property="status", type="integer", example=200),
     *       @OA\Property(
     *         property="data",
     *         type="array",
     *         @OA\Items(
     *           @OA\Property(property="name",  type="string", example="manage_users"),
     *           @OA\Property(property="label", type="string", example="Gestionar usuarios")
     *         )
     *       )
     *     )
     *   ),
     *   @OA\Response(response=403, description="Sin permiso")
     * )
     */
    public function permissions(): JsonResponse
    {
        abort_unless(auth()->user()?->can('manage_roles'), 403, 'Sin permiso.');

        $perms = Permission::where('guard_name', 'api')
            ->orderBy('name')
            ->get(['name', 'label'])
            ->map(fn($p) => [
                'name'  => $p->name,
                'label' => $p->label ?? $p->name,
            ])->values();

        return response()->json(['status' => 200, 'data' => $perms]);
    }

    /**
     * @OA\Get(
     *   path="/api/v1/users",
     *   tags={"Roles & Permissions"},
     *   summary="Listar usuarios con sus roles asignados",
     *   security={{"bearerAuth":{}}},
     *   @OA\Response(
     *     response=200,
     *     description="Lista de usuarios con roles",
     *     @OA\JsonContent(
     *       @OA\Property(property="status", type="integer", example=200),
     *       @OA\Property(
     *         property="data",
     *         type="array",
     *         @OA\Items(
     *           @OA\Property(property="id",     type="integer", example=1),
     *           @OA\Property(property="name",   type="string",  example="Juan Perez"),
     *           @OA\Property(property="email",  type="string",  example="juan@example.com"),
     *           @OA\Property(property="avatar", type="string",  nullable=true, example=null),
     *           @OA\Property(
     *             property="roles",
     *             type="array",
     *             @OA\Items(type="string", example="Operator")
     *           )
     *         )
     *       )
     *     )
     *   ),
     *   @OA\Response(response=403, description="Sin permiso")
     * )
     */
    public function users(): JsonResponse
    {
        abort_unless(auth()->user()?->can('manage_roles'), 403, 'Sin permiso.');

        $users = User::with('roles')
            ->orderBy('name')
            ->get()
            ->map(fn(User $u) => [
                'id'     => $u->id,
                'name'   => $u->name,
                'email'  => $u->email,
                'avatar' => $u->photoUrl,
                'roles'  => $u->roles->pluck('name')->values(),
            ]);

        return response()->json(['status' => 200, 'data' => $users]);
    }

    /**
     * @OA\Put(
     *   path="/api/v1/roles/{role}/permissions",
     *   tags={"Roles & Permissions"},
     *   summary="Sincronizar los permisos de un rol",
     *   description="Reemplaza completamente los permisos del rol indicado.",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(
     *     name="role",
     *     in="path",
     *     required=true,
     *     description="Nombre del rol",
     *     @OA\Schema(type="string", example="Manager")
     *   ),
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *       required={"permissions"},
     *       @OA\Property(
     *         property="permissions",
     *         type="array",
     *         @OA\Items(type="string", example="view_vessels"),
     *         description="Lista de nombres de permisos a asignar al rol"
     *       )
     *     )
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Permisos actualizados",
     *     @OA\JsonContent(
     *       @OA\Property(property="status",  type="integer", example=200),
     *       @OA\Property(property="message", type="string",  example="Permisos de Manager actualizados."),
     *       @OA\Property(
     *         property="data",
     *         type="object",
     *         @OA\Property(property="role", type="string", example="Manager"),
     *         @OA\Property(
     *           property="permissions",
     *           type="array",
     *           @OA\Items(type="string", example="view_vessels")
     *         )
     *       )
     *     )
     *   ),
     *   @OA\Response(response=403, description="Sin permiso"),
     *   @OA\Response(response=404, description="Rol no encontrado"),
     *   @OA\Response(response=422, description="Errores de validacion")
     * )
     */
    public function syncPermissions(Request $request, string $role): JsonResponse
    {
        abort_unless(auth()->user()?->can('manage_roles'), 403, 'Sin permiso.');

        $validated = $request->validate([
            'permissions'   => ['required', 'array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ]);

        $roleModel = Role::where('name', $role)->where('guard_name', 'api')->firstOrFail();
        $roleModel->syncPermissions($validated['permissions']);

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        return response()->json([
            'status'  => 200,
            'message' => "Permisos de '{$role}' actualizados.",
            'data'    => [
                'role'        => $role,
                'permissions' => $roleModel->permissions->pluck('name')->values(),
            ],
        ]);
    }
}
