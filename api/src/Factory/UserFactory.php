<?php

declare(strict_types=1);

namespace App\Factory;

use App\Entity\User;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<User>
 */
final class UserFactory extends PersistentObjectFactory
{
    private const string DEFAULT_PASSWORD = 'password';

    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    #[\Override]
    public static function class(): string
    {
        return User::class;
    }

    public function admin(): static
    {
        return $this->with(['roles' => [User::ROLE_ADMIN]]);
    }

    public function instructor(): static
    {
        return $this->with(['roles' => [User::ROLE_INSTRUCTOR]]);
    }

    public function student(): static
    {
        return $this->with(['roles' => [User::ROLE_STUDENT]]);
    }

    #[\Override]
    protected function defaults(): array
    {
        return [
            'email' => self::faker()->unique()->safeEmail(),
            'roles' => [],
            'password' => self::DEFAULT_PASSWORD,
        ];
    }

    #[\Override]
    protected function initialize(): static
    {
        return $this->afterInstantiate(function (User $user): void {
            $user->setPassword($this->passwordHasher->hashPassword($user, $user->getPassword()));
        });
    }
}
