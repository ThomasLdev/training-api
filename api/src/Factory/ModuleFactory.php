<?php

namespace App\Factory;

use App\Entity\Module;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

use function Zenstruck\Foundry\lazy;

/**
 * @extends PersistentObjectFactory<Module>
 */
final class ModuleFactory extends PersistentObjectFactory
{
    private static int $positionCounter = 0;

    public function __construct()
    {
    }

    #[\Override]
    public static function class(): string
    {
        return Module::class;
    }

    #[\Override]
    protected function defaults(): array|callable
    {
        return [
            'title' => self::faker()->sentence(4),
            'content' => self::faker()->paragraphs(5, true),
            'position' => self::$positionCounter++,
            'durationInMinutes' => self::faker()->numberBetween(10, 120),
            'course' => lazy(fn () => CourseFactory::randomOrCreate()),
        ];
    }

    #[\Override]
    protected function initialize(): static
    {
        return $this;
    }
}
