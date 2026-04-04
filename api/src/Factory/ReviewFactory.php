<?php

namespace App\Factory;

use App\Entity\Review;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

use function Zenstruck\Foundry\lazy;

/**
 * @extends PersistentObjectFactory<Review>
 */
final class ReviewFactory extends PersistentObjectFactory
{
    public function __construct()
    {
    }

    #[\Override]
    public static function class(): string
    {
        return Review::class;
    }

    #[\Override]
    protected function defaults(): array|callable
    {
        return [
            'rating' => self::faker()->numberBetween(1, 5),
            'comment' => self::faker()->paragraphs(1, true),
            'student' => lazy(fn () => StudentFactory::randomOrCreate()),
            'course' => lazy(fn () => CourseFactory::randomOrCreate()),
        ];
    }

    #[\Override]
    protected function initialize(): static
    {
        return $this;
    }
}
