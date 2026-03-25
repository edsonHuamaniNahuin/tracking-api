<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name'                         => $this->faker->name(),
            'username'                     => $this->faker->unique()->userName(),
            'email'                        => $this->faker->unique()->safeEmail(),
            'email_verified_at'            => now(),
            'password'                     => static::$password ??= Hash::make('password'),
            'remember_token'               => Str::random(10),

            // Campos adicionales:
            'photo_url'                    => null, // podrías enlazar a algún placeholder si quieres
            'phone'                        => $this->faker->phoneNumber(),
            'bio'                          => $this->faker->optional()->paragraph(),
            'location'                     => $this->faker->optional()->city(),
            'notifications_count'          => 0,
            'newsletter_subscribed'        => $this->faker->boolean(30),
            'public_profile'               => $this->faker->boolean(50),
            'show_online_status'           => $this->faker->boolean(50),
            'two_factor_enabled'           => false,
            'email_notifications_enabled'  => $this->faker->boolean(50),
            'push_notifications_enabled'   => $this->faker->boolean(50),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
