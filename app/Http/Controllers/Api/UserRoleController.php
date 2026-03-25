<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\UserRoleService;
use App\DTO\AuthResponse;

/**
 * @OA\Tag(
 *   name="UserRoles",
 *   description="Asignar y revocar roles de usuario"
 * )
 */
class UserRoleController extends Controller
{
    public function __construct(protected UserRoleService $service)
    {
        $this->middleware('auth:api');
    }

    /**
     * @OA\Post(
     *   path="/api/v1/users/{user}/roles",
     *   tags={"UserRoles"},
     *   summary="Asignar rol a usuario",
     *   @OA\Parameter(name="user", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\RequestBody(@OA\JsonContent(@OA\Property(property="role", type="string"))),
     *   @OA\Response(response=200, description="Rol asignado", @OA\JsonContent(ref="#/components/schemas/AuthResponse"))
     * )
     */
    public function assign(Request $request, int $user): AuthResponse
    {
        $role = $request->validate(['role' => 'required|string'])['role'];
        return $this->service->assignRole($user, $role);
    }

    /**
     * @OA\Delete(
     *   path="/api/v1/users/{user}/roles/{role}",
     *   tags={"UserRoles"},
     *   summary="Revocar rol de usuario",
     *   @OA\Parameter(name="user", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Parameter(name="role", in="path", required=true, @OA\Schema(type="string")),
     *   @OA\Response(response=200, description="Rol revocado", @OA\JsonContent(ref="#/components/schemas/AuthResponse"))
     * )
     */
    public function revoke(int $user, string $role): AuthResponse
    {
        return $this->service->revokeRole($user, $role);
    }
}
