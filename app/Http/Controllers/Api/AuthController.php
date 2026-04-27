<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginHttpRequest;
use App\DTO\AuthResponse;
use App\Services\AuthService;

/**
 * Grupo de endpoints de autenticación.
 *
 * @OA\Tag(
 *   name="Auth",
 *   description="Endpoints para iniciar sesión, refrescar token y cerrar sesión"
 * )
 *
 * @OA\PathItem(
 *   path="/api/login",
 *   summary="Operaciones de autenticación"
 * )
 */
class AuthController extends Controller
{
    public function __construct(private AuthService $authService)
    {
        // 'refresh' también excluido: acepta tokens expirados dentro de JWT_REFRESH_TTL.
        // La ruta ya está fuera del grupo auth:api en routes/api.php; este except
        // evita que el middleware del controlador rechace el token antes de llegar al método.
        $this->middleware('auth:api', ['except' => ['login', 'refresh']]);
    }

    /**
     * @OA\Post(
     *   path="/api/login",
     *   tags={"Auth"},
     *   summary="Iniciar sesión",
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(ref="#/components/schemas/LoginRequest")
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Autenticación exitosa",
     *     @OA\JsonContent(ref="#/components/schemas/AuthResponse")
     *   ),
     *   @OA\Response(
     *     response=401,
     *     description="Credenciales inválidas",
     *     @OA\JsonContent(
     *       @OA\Property(property="status",  type="integer", example=401),
     *       @OA\Property(property="message", type="string",  example="Credenciales inválidas"),
     *       @OA\Property(property="data",    type="null",    example=null)
     *     )
     *   )
     * )
     */
    public function login(LoginHttpRequest $request): AuthResponse
    {
        return $this->authService->login($request->validated());
    }

    /**
     * Obtiene los datos del usuario autenticado.
     *
     * @OA\Get(
     *   path="/api/me",
     *   tags={"Auth"},
     *   summary="Obtener perfil",
     *   description="Retorna la información del usuario actualmente autenticado.",
     *   security={{"bearerAuth":{}}},
     *
     *   @OA\Response(
     *     response=200,
     *     description="Datos de usuario",
     *     @OA\JsonContent(ref="#/components/schemas/AuthResponse")
     *   )
     * )
     */
    public function me(): AuthResponse
    {
        return $this->authService->me();
    }

    /**
     * Cierra la sesión invalidando el token.
     *
     * @OA\Post(
     *   path="/api/logout",
     *   tags={"Auth"},
     *   summary="Cerrar sesión",
     *   security={{"bearerAuth":{}}},
     *
     *   @OA\Response(
     *     response=200,
     *     description="Sesión cerrada correctamente",
     *     @OA\JsonContent(
     *       @OA\Property(property="status",  type="integer", example=200),
     *       @OA\Property(property="message", type="string",  example="Sesión cerrada correctamente"),
     *       @OA\Property(property="data",    type="null",    example=null)
     *     )
     *   )
     * )
     */
    public function logout(): AuthResponse
    {
        return $this->authService->logout();
    }

    /**
     * Refresca el token JWT.
     *
     * @OA\Post(
     *   path="/api/refresh",
     *   tags={"Auth"},
     *   summary="Refrescar token",
     *   security={{"bearerAuth":{}}},
     *
     *   @OA\Response(
     *     response=200,
     *     description="Token refrescado",
     *     @OA\JsonContent(ref="#/components/schemas/AuthResponse")
     *   )
     * )
     */
    public function refresh(): AuthResponse
    {
        return $this->authService->refresh();
    }
}
