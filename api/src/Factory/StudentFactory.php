<?php

declare(strict_types=1);

namespace App\Factory;

use App\Entity\Student;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<Student>
 */
final class StudentFactory extends PersistentObjectFactory
{
    public function __construct()
    {
    }

    #[\Override]
    public static function class(): string
    {
        return Student::class;
    }

    #[\Override]
    protected function defaults(): array
    {
        $email = self::faker()->unique()->safeEmail();

        return [
            'user' => UserFactory::new()->student()->with(['email' => $email]),
            'firstName' => self::faker()->firstName(),
            'lastName' => self::faker()->lastName(),
            'email' => $email,
        ];
    }

    #[\Override]
    protected function initialize(): static
    {
        return $this;
    }
}
