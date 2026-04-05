<?php

declare(strict_types=1);

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
    protected function defaults(): array
    {
        return [
            'rating' => self::faker()->numberBetween(1, 5),
            'comment' => self::faker()->paragraphs(1, true),
            'student' => lazy(fn (): object => StudentFactory::randomOrCreate()),
            'course' => lazy(fn (): object => CourseFactory::randomOrCreate()),
        ];
    }

    #[\Override]
    protected function initialize(): static
    {
        return $this;
    }
}
