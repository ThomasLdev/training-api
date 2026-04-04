<?php

namespace App\Factory;

use App\Entity\Course;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

use function Zenstruck\Foundry\lazy;

/**
 * @extends PersistentObjectFactory<Course>
 */
final class CourseFactory extends PersistentObjectFactory
{
    private const array SUBJECTS = [
        'Symfony', 'API Platform', 'React', 'Vue.js', 'Docker', 'Kubernetes',
        'PostgreSQL', 'MongoDB', 'Redis', 'GraphQL', 'TypeScript', 'PHP 8',
        'Python', 'Machine Learning', 'DevOps', 'CI/CD', 'Sécurité web',
        'Design Patterns', 'Architecture hexagonale', 'Microservices',
        'TDD', 'DDD', 'CQRS', 'Event Sourcing', 'Elasticsearch',
        'RabbitMQ', 'Kafka', 'AWS', 'Terraform', 'Git avancé',
    ];

    private const array FORMATS = [
        '%s pour les débutants',
        '%s : de zéro à la production',
        '%s avancé : guide complet',
        'Maîtriser %s en pratique',
        'Introduction à %s',
        '%s : bonnes pratiques et patterns',
        '%s : optimisation et performance',
        'Formation complète %s',
    ];

    public function __construct()
    {
    }

    #[\Override]
    public static function class(): string
    {
        return Course::class;
    }

    #[\Override]
    protected function defaults(): array
    {
        return [
            'title' => sprintf(
                self::FORMATS[array_rand(self::FORMATS)],
                self::SUBJECTS[array_rand(self::SUBJECTS)],
            ),
            'description' => self::faker()->paragraphs(3, true),
            'level' => self::faker()->randomElement([
                Course::LEVEL_BEGINNER,
                Course::LEVEL_INTERMEDIATE,
                Course::LEVEL_ADVANCED,
            ]),
            'priceInCents' => self::faker()->randomElement([0, 1990, 2990, 4990, 7990, 9990, 14990]),
            'maxStudents' => self::faker()->numberBetween(10, 50),
            'status' => Course::STATUS_PUBLISHED,
            'publishedAt' => \DateTimeImmutable::createFromMutable(
                self::faker()->dateTimeBetween('-1 year', '-1 month'),
            ),
            'instructor' => lazy(fn (): object => InstructorFactory::randomOrCreate()),
        ];
    }

    public function draft(): static
    {
        return $this->with([
            'status' => Course::STATUS_DRAFT,
            'publishedAt' => null,
        ]);
    }

    public function archived(): static
    {
        return $this->with([
            'status' => Course::STATUS_ARCHIVED,
        ]);
    }

    public function free(): static
    {
        return $this->with([
            'priceInCents' => 0,
        ]);
    }

    #[\Override]
    protected function initialize(): static
    {
        return $this;
    }
}
