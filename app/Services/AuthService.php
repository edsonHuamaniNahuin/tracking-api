<?php

namespace App\Services;

use App\DTO\AuthRequest;
use App\DTO\AuthResponse;
use App\Repositories\UserRepository;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Auth;

class AuthService
{
    public function __construct(
        protected UserRepository $users
    ) {}

    /**
     * Intenta autenticar y retorna AuthResponse (data nula en error).
     */
    /**
     * @param  array{email:string,password:string}  $credentials
     */
    public function login(array $credentials): AuthResponse
    {
        if (! $token = JWTAuth::attempt($credentials)) {
            return new AuthResponse(
                data: null,
                status: 401,
                message: 'Credenciales inválidas'
            );
        }

        $user = $this->users->findByEmail($credentials['email']);

        return new AuthResponse(
            data: [
                'user'         => [
                    'id'       => $user->id,
                    'email'    => $user->email,
                    'name'     => $user->name,
                    'username' => $user->username,
                    'photoUrl' => $user->photoUrl,
                    'avatar'   => $user->avatar
                ],
                'access_token' => $token,
                'token_type'   => 'bearer',
                'expires_in'   => JWTAuth::factory()->getTTL() * 60,
            ],
            status: 200,
            message: 'Autenticación exitosa'
        );
    }

    /**
     * Devuelve datos de usuario actual con status 200.
     */
    public function me(): AuthResponse
    {
        $user =  Auth::user();
        return new AuthResponse(
            data: [
                'user' => [
                    'id'                                    => $user->id,
                    'email'                                 => $user->email,
                    'name'                                  => $user->name,
                    'username'                              => $user->username,
                    'photoUrl'                              => $user->photoUrl,
                    'avatar'                                => $user->avatar,
                    'notifications_count'                   => $user->notifications_count,
                    'newsletter_subscribed'                 => $user->newsletter_subscribed,
                    'public_profile'                        => $user->public_profile,
                    'show_online_status'                    => $user->show_online_status,
                    'phone'                                 => $user->phone,
                    'bio'                                   => $user->bio,
                    'location'                              => $user->location,
                    'two_factor_enabled'                    => $user->phone,
                    'email_notifications_enabled'           => $user->email_notifications_enabled,
                    'push_notifications_enabled'            => $user->push_notifications_enabled,

                ],
            ],
            status: 200,
            message: 'Datos de usuario'
        );
    }

    /**
     * Invalida el token y retorna mensaje.
     */
    public function logout(): AuthResponse
    {
        JWTAuth::invalidate(JWTAuth::getToken());
        return new AuthResponse(
            data: null,
            status: 200,
            message: 'Sesión cerrada correctamente'
        );
    }

    /**
     * Refresca token y retorna nuevo payload.
     */
    public function refresh(): AuthResponse
    {
        $token = JWTAuth::refresh();
        $user  =  Auth::user();

        return new AuthResponse(
            data: [
                'user'         => [
                    'id'       => $user->id,
                    'email'    => $user->email,
                    'name'     => $user->name,
                    'username' => $user->username,
                    'photoUrl' => $user->photoUrl,
                ],
                'access_token' => $token,
                'token_type'   => 'bearer',
                'expires_in'   => JWTAuth::factory()->getTTL() * 60,
            ],
            status: 200,
            message: 'Token refrescado'
        );
    }
}
