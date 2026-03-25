<?php

namespace App\Repositories;

use App\Models\User;

class UserRepository
{
    /**
     * Busca un usuario por su correo electrónico.
     *
     * @param string $email
     * @return User|null
     */
    public function findByEmail(string $email): ?User
    {
        return User::where('email', $email)->first();
    }

    /**
     * Busca un usuario por su ID.
     *
     * @param int $id
     * @return User|null
     */
    public function findById(int $id): ?User
    {
        return User::find($id);
    }

    /**
     * Actualiza datos de un usuario.
     */
    public function update(int $id, array $data): User
    {
        $user = User::findOrFail($id);
        $user->fill($data);
        $user->save();
        return $user;
    }
}
