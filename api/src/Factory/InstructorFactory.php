<?php

declare(strict_types=1);

namespace App\Factory;

use App\Entity\Instructor;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<Instructor>
 */
final class InstructorFactory extends PersistentObjectFactory
{
    private const array SPECIALTIES = [
        'PHP & Symfony',
        'JavaScript & React',
        'DevOps & CI/CD',
        'Data Science & Python',
        'Mobile Development',
        'Cloud Architecture',
        'Cybersecurity',
        'Machine Learning',
        'UI/UX Design',
        'Blockchain & Web3',
    ];

    public function __construct()
    {
    }

    #[\Override]
    public static function class(): string
    {
        return Instructor::class;
    }

    #[\Override]
    protected function defaults(): array
    {
        return [
            'user' => UserFactory::new()->instructor(),
            'firstName' => self::faker()->firstName(),
            'lastName' => self::faker()->lastName(),
            'bio' => self::faker()->paragraphs(2, true),
            'specialty' => self::faker()->randomElement(self::SPECIALTIES),
        ];
    }

    #[\Override]
    protected function initialize(): static
    {
        return $this;
    }
}
