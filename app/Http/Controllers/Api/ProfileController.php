<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateProfileRequest;
use App\Http\Requests\ChangePasswordRequest;
use App\DTO\ProfileUpdateDto;
use App\DTO\ChangePasswordDto;
use App\DTO\AuthResponse;
use App\DTO\GenericResponse;
use App\Services\ProfileService;
use App\Services\AuthService;

/**
 * @OA\Tag(
 *   name="Profile",
 *   description="Endpoints para gestión de perfil de usuario"
 * )
 */
class ProfileController extends Controller
{
    public function __construct(
        protected AuthService $authService,
        protected ProfileService $profileService
    ) {
        $this->middleware('auth:api');
    }

    /**
     * @OA\Get(
     *   path="/api/v1/user",
     *   tags={"Profile"},
     *   summary="Mostrar perfil",
     *   security={{"bearerAuth":{}}},
     *   @OA\Response(response=200, description="Perfil obtenido", @OA\JsonContent(ref="#/components/schemas/AuthResponse"))
     * )
     */
    public function show(): AuthResponse
    {
        return $this->authService->me();
    }

     /**
     * @OA\Put(
     *   path="/api/v1/user",
     *   tags={"Profile"},
     *   summary="Actualizar perfil",
     *   security={{"bearerAuth":{}}},
     *   @OA\RequestBody(ref="#/components/schemas/UpdateProfileRequest"),
     *   @OA\Response(response=200, description="Perfil actualizado", @OA\JsonContent(ref="#/components/schemas/GenericResponse"))
     * )
     */
    public function update(UpdateProfileRequest $request): GenericResponse
    {
        return $this->profileService->updateProfile($request->validated());
    }

    /**
     * @OA\Put(
     *   path="/api/v1/user/password",
     *   tags={"Profile"},
     *   summary="Cambiar contraseña",
     *   security={{"bearerAuth":{}}},
     *   @OA\RequestBody(ref="#/components/schemas/ChangePasswordRequest"),
     *   @OA\Response(response=200, description="Contraseña cambiada", @OA\JsonContent(ref="#/components/schemas/AuthResponse")),
     *   @OA\Response(response=400, description="Error de contraseña", @OA\JsonContent(ref="#/components/schemas/AuthResponse"))
     * )
     */
    public function changePassword(ChangePasswordRequest $request): GenericResponse
    {
        $dto = ChangePasswordDto::fromRequest($request->validated());
        return $this->profileService->changePassword($dto);
    }


    /**
     * @OA\Post(
     *   path="/api/v1/user/notifications/email/enable",
     *   tags={"Profile"},
     *   summary="Habilitar Notificaciones por Email",
     *   security={{"bearerAuth":{}}},
     *   @OA\Response(
     *     response=200,
     *     description="Notificaciones por email habilitadas",
     *     @OA\JsonContent(ref="#/components/schemas/GenericResponse")
     *   )
     * )
     */
    public function enableEmailNotifications(): GenericResponse
    {
        return $this->profileService->enableEmailNotifications();
    }

    /**
     * @OA\Post(
     *   path="/api/v1/user/notifications/email/disable",
     *   tags={"Profile"},
     *   summary="Deshabilitar Notificaciones por Email",
     *   security={{"bearerAuth":{}}},
     *   @OA\Response(
     *     response=200,
     *     description="Notificaciones por email deshabilitadas",
     *     @OA\JsonContent(ref="#/components/schemas/GenericResponse")
     *   )
     * )
     */
    public function disableEmailNotifications(): GenericResponse
    {
        return $this->profileService->disableEmailNotifications();
    }

    /**
     * @OA\Post(
     *   path="/api/v1/user/notifications/push/enable",
     *   tags={"Profile"},
     *   summary="Habilitar Notificaciones Push",
     *   security={{"bearerAuth":{}}},
     *   @OA\Response(
     *     response=200,
     *     description="Notificaciones Push habilitadas",
     *     @OA\JsonContent(ref="#/components/schemas/GenericResponse")
     *   )
     * )
     */
    public function enablePushNotifications(): GenericResponse
    {
        return $this->profileService->enablePushNotifications();
    }

    /**
     * @OA\Post(
     *   path="/api/v1/user/notifications/push/disable",
     *   tags={"Profile"},
     *   summary="Deshabilitar Notificaciones Push",
     *   security={{"bearerAuth":{}}},
     *   @OA\Response(
     *     response=200,
     *     description="Notificaciones Push deshabilitadas",
     *     @OA\JsonContent(ref="#/components/schemas/GenericResponse")
     *   )
     * )
     */
    public function disablePushNotifications(): GenericResponse
    {
        return $this->profileService->disablePushNotifications();
    }

    /** @OA\Post(
     *    path="/api/v1/user/newsletter/enable",
     *    tags={"Profile"},
     *    summary="Habilitar newsletter",
     *    security={{"bearerAuth":{}}},
     *    @OA\Response(response=200, description="Newsletter habilitada", @OA\JsonContent(ref="#/components/schemas/AuthResponse"))
     * )
     */
    public function enableNewsletter(): GenericResponse
    {
        return $this->profileService->enableNewsletter();
    }

    /** @OA\Post(
     *    path="/api/v1/user/newsletter/disable",
     *    tags={"Profile"},
     *    summary="Deshabilitar newsletter",
     *    security={{"bearerAuth":{}}},
     *    @OA\Response(response=200, description="Newsletter deshabilitada", @OA\JsonContent(ref="#/components/schemas/AuthResponse"))
     * )
     */
    public function disableNewsletter(): GenericResponse
    {
        return $this->profileService->disableNewsletter();
    }




     /** @OA\Post(
     *    path="/api/v1/user/2fa/enable",
     *    tags={"Profile"},
     *    summary="Habilitar 2FA",
     *    security={{"bearerAuth":{}}},
     *    @OA\Response(response=200, description="2FA habilitada", @OA\JsonContent(ref="#/components/schemas/AuthResponse"))
     * )
     */
    public function enableTwoFactor(): GenericResponse
    {
        return $this->profileService->enableTwoFactor();
    }

    /** @OA\Post(
     *    path="/api/v1/user/2fa/disable",
     *    tags={"Profile"},
     *    summary="Deshabilitar 2FA",
     *    security={{"bearerAuth":{}}},
     *    @OA\Response(response=200, description="2FA deshabilitada", @OA\JsonContent(ref="#/components/schemas/AuthResponse"))
     * )
     */
    public function disableTwoFactor(): GenericResponse
    {
        return $this->profileService->disableTwoFactor();
    }

    /** @OA\Post(
     *    path="/api/v1/user/profile/private",
     *    tags={"Profile"},
     *    summary="Deshabilitar perfil público",
     *    security={{"bearerAuth":{}}},
     *    @OA\Response(response=200, description="Perfil público deshabilitado", @OA\JsonContent(ref="#/components/schemas/AuthResponse"))
     * )
     */
    public function disablePublicProfile(): GenericResponse
    {
        return $this->profileService->disablePublicProfile();
    }

    /** @OA\Post(
     *    path="/api/v1/user/profile/public",
     *    tags={"Profile"},
     *    summary="Habilitar perfil público",
     *    security={{"bearerAuth":{}}},
     *    @OA\Response(response=200, description="Perfil público habilitado", @OA\JsonContent(ref="#/components/schemas/AuthResponse"))
     * )
     */
    public function enablePublicProfile(): GenericResponse
    {
        return $this->profileService->enablePublicProfile();
    }

    /** @OA\Post(
     *    path="/api/v1/user/online-status/show",
     *    tags={"Profile"},
     *    summary="Mostrar estado online",
     *    security={{"bearerAuth":{}}},
     *    @OA\Response(response=200, description="Estado online visible", @OA\JsonContent(ref="#/components/schemas/AuthResponse"))
     * )
     */
    public function showOnlineStatus(): GenericResponse
    {
        return $this->profileService->showOnlineStatus();
    }

    /** @OA\Post(
     *    path="/api/v1/user/online-status/hide",
     *    tags={"Profile"},
     *    summary="Ocultar estado online",
     *    security={{"bearerAuth":{}}},
     *    @OA\Response(response=200, description="Estado online oculto", @OA\JsonContent(ref="#/components/schemas/AuthResponse"))
     * )
     */
    public function hideOnlineStatus(): GenericResponse
    {
        return $this->profileService->hideOnlineStatus();
    }


}
