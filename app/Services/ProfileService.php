<?php

namespace App\Services;

use App\DTO\ProfileUpdateDto;
use App\DTO\ChangePasswordDto;
use App\DTO\PreferencesDto;
use App\DTO\AuthResponse;
use App\DTO\GenericResponse;
use App\Repositories\UserRepository;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class ProfileService
{
    public function __construct(protected UserRepository $users) {}

    public function updateProfile(array $validated): GenericResponse
    {
        $allowed = ['name', 'username', 'email', 'phone', 'bio', 'location'];
        $data    = array_intersect_key($validated, array_flip($allowed));
        $user = $this->users->update(Auth::id(), $data);

        return new GenericResponse(
            data: ['user' => $user->only($allowed)],
            message: 'Perfil actualizado correctamente'
        );
    }

    public function changePassword(ChangePasswordDto $dto): GenericResponse
    {
        $user = Auth::user();
        if (! Hash::check($dto->currentPassword, $user->password)) {
            return new GenericResponse(
                data: null,
                status: 400,
                message: 'Contraseña actual incorrecta'
            );
        }
        $this->users->update($user->id, ['password' => Hash::make($dto->newPassword)]);
        return new GenericResponse(
            data: null,
            status: 200,
            message: 'Contraseña cambiada correctamente'
        );
    }

    public function updatePreferences(PreferencesDto $dto): GenericResponse
    {
        $data = $dto->toArray();
        $user = $this->users->update(Auth::id(), $data);
        return new GenericResponse(
            data: ['user' => $user->toArray()],
            status: 200,
            message: 'Preferencias actualizadas correctamente'
        );
    }

    public function enableNewsletter(): GenericResponse
    {
        $user = $this->users->update(Auth::id(), ['newsletter_subscribed' => true]);
        $public = $user->only(['id', 'newsletter_subscribed']);

        return new GenericResponse(
            data: ['user' => $public],
            status: 200,
            message: 'Newsletter suscripción habilitada'
        );
    }

    public function disableNewsletter(): GenericResponse
    {
        $user = $this->users->update(Auth::id(), ['newsletter_subscribed' => false]);
        $public = $user->only(['id', 'newsletter_subscribed']);
        return new GenericResponse(
            data: ['user' => $public],
            status: 200,
            message: 'Newsletter suscripción deshabilitada'
        );
    }

    public function enableTwoFactor(): GenericResponse
    {
        $user = $this->users->update(Auth::id(), ['two_factor_enabled' => true]);
        $public = $user->only(['id', 'two_factor_enabled']);
        return new GenericResponse(
            data: ['user' => $public],
            status: 200,
            message: '2FA habilitada'
        );
    }

    public function disableTwoFactor(): GenericResponse
    {
        $user = $this->users->update(Auth::id(), ['two_factor_enabled' => false]);
        $public = $user->only(['id', 'two_factor_enabled']);
        return new GenericResponse(
            data: ['user' => $public],
            status: 200,
            message: '2FA deshabilitada'
        );
    }

    public function enablePublicProfile(): GenericResponse
    {
        $user = $this->users->update(Auth::id(), ['public_profile' => true]);
        $public = $user->only(['id', 'public_profile']);
        return new GenericResponse(
            data: ['user' => $public],
            status: 200,
            message: 'Perfil público habilitado'
        );
    }


    public function disablePublicProfile(): GenericResponse
    {
        $user = $this->users->update(Auth::id(), ['public_profile' => false]);
        $public = $user->only(['id', 'public_profile']);
        return new GenericResponse(
            data: ['user' => $public],
            status: 200,
            message: 'Perfil público deshabilitado'
        );
    }

    public function showOnlineStatus(): GenericResponse
    {
        $user = $this->users->update(Auth::id(), ['show_online_status' => true]);
        $public = $user->only(['id', 'show_online_status']);
        return new GenericResponse(
            data: ['user' => $public],
            status: 200,
            message: 'Estado online visible'
        );
    }

    public function hideOnlineStatus(): GenericResponse
    {
        $user = $this->users->update(Auth::id(), ['show_online_status' => false]);
        $public = $user->only(['id', 'show_online_status']);
        return new GenericResponse(
            data: ['user' => $public],
            status: 200,
            message: 'Estado online oculto'
        );
    }


    /**
     * Habilita notificaciones por email.
     */
    public function enableEmailNotifications(): GenericResponse
    {
        $user = $this->users->update(Auth::id(), [
            'email_notifications_enabled' => true
        ]);
        $public = $user->only(['id', 'email_notifications_enabled']);

        return new GenericResponse(
            data: ['user' => $public],
            status: 200,
            message: 'Notificaciones por email habilitadas'
        );
    }

    /**
     * Deshabilita notificaciones por email.
     */
    public function disableEmailNotifications(): GenericResponse
    {
        $user = $this->users->update(Auth::id(), [
            'email_notifications_enabled' => false
        ]);
        $public = $user->only(['id', 'email_notifications_enabled']);

        return new GenericResponse(
            data: ['user' => $public],
            status: 200,
            message: 'Notificaciones por email deshabilitadas'
        );
    }

    /**
     * Habilita notificaciones push.
     */
    public function enablePushNotifications(): GenericResponse
    {
        $user = $this->users->update(Auth::id(), [
            'push_notifications_enabled' => true
        ]);
         $public = $user->only(['id', 'push_notifications_enabled']);

        return new GenericResponse(
            data: ['user' => $public],
            status: 200,
            message: 'Notificaciones Push habilitadas'
        );
    }

    /**
     * Deshabilita notificaciones push.
     */
    public function disablePushNotifications(): GenericResponse
    {
        $user = $this->users->update(Auth::id(), [
            'push_notifications_enabled' => false
        ]);
        $public = $user->only(['id', 'push_notifications_enabled']);

        return new GenericResponse(
            data: ['user' => $public],
            status: 200,
            message: 'Notificaciones Push deshabilitadas'
        );
    }
}
