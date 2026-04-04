<?php

namespace App\Factory;

use App\Entity\Enrollment;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

use function Zenstruck\Foundry\lazy;

/**
 * @extends PersistentObjectFactory<Enrollment>
 */
final class EnrollmentFactory extends PersistentObjectFactory
{
    public function __construct()
    {
    }

    #[\Override]
    public static function class(): string
    {
        return Enrollment::class;
    }

    #[\Override]
    protected function defaults(): array|callable
    {
        $progress = self::faker()->numberBetween(0, 100);

        return [
            'student' => lazy(fn () => StudentFactory::randomOrCreate()),
            'course' => lazy(fn () => CourseFactory::randomOrCreate()),
            'progressPercent' => $progress,
            'finalGrade' => $progress === 100 ? self::faker()->randomFloat(1, 8, 20) : null,
            'status' => $progress === 100 ? Enrollment::STATUS_COMPLETED : Enrollment::STATUS_ACTIVE,
            'paidPriceInCents' => self::faker()->randomElement([0, 1990, 2990, 4990, 7990]),
        ];
    }

    public function completed(): static
    {
        return $this->with([
            'progressPercent' => 100,
            'finalGrade' => self::faker()->randomFloat(1, 8, 20),
            'status' => Enrollment::STATUS_COMPLETED,
        ]);
    }

    public function cancelled(): static
    {
        return $this->with([
            'status' => Enrollment::STATUS_CANCELLED,
            'progressPercent' => self::faker()->numberBetween(0, 50),
            'finalGrade' => null,
        ]);
    }

    #[\Override]
    protected function initialize(): static
    {
        return $this;
    }
}
