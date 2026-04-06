<?php

declare(strict_types=1);

namespace App\Story;

use App\Entity\Course;
use App\Entity\Instructor;
use App\Entity\Student;
use App\Factory\CourseFactory;
use App\Factory\EnrollmentFactory;
use App\Factory\InstructorFactory;
use App\Factory\ModuleFactory;
use App\Factory\ReviewFactory;
use App\Factory\StudentFactory;
use App\Factory\UserFactory;
use Zenstruck\Foundry\Attribute\AsFixture;
use Zenstruck\Foundry\Story;

use function Zenstruck\Foundry\faker;
use function Zenstruck\Foundry\Persistence\flush_after;

#[AsFixture(name: 'main')]
final class AppStory extends Story
{
    public function build(): void
    {
        // 10 instructeurs + admin (password: "password")
        /** @var list<Instructor> $instructors */
        $instructors = flush_after(function (): array {
            UserFactory::createOne(['email' => 'admin@training.local', 'roles' => ['ROLE_ADMIN']]);

            return InstructorFactory::createMany(10);
        });

        /** @var list<Course> $publishedCourses */
        $publishedCourses = flush_after(fn (): array => CourseFactory::createMany(30, fn (): array => [
            'instructor' => faker()->randomElement($instructors),
        ]));

        flush_after(function () use ($instructors): void {
            foreach (faker()->randomElements($instructors, 5) as $instructor) {
                CourseFactory::new()->draft()->create(['instructor' => $instructor]);
            }
            foreach (faker()->randomElements($instructors, 3) as $instructor) {
                CourseFactory::new()->archived()->create(['instructor' => $instructor]);
            }
        });

        // 3-6 modules par cours publié
        flush_after(function () use ($publishedCourses): void {
            foreach ($publishedCourses as $course) {
                $moduleCount = faker()->numberBetween(3, 6);
                for ($i = 0; $i < $moduleCount; $i++) {
                    ModuleFactory::createOne([
                        'course' => $course,
                        'position' => $i,
                    ]);
                }
            }
        });

        // 100 étudiants
        /** @var list<Student> $students */
        $students = flush_after(fn (): array => StudentFactory::createMany(100));

        // Chaque étudiant inscrit à 1-4 cours
        /** @var array<string, true> $usedPairs */
        $usedPairs = [];
        flush_after(function () use ($students, $publishedCourses, &$usedPairs): void {
            /** @var Student $student */
            foreach ($students as $student) {
                $courseCount = faker()->numberBetween(1, 4);
                $selectedCourses = faker()->randomElements($publishedCourses, $courseCount);

                /** @var Course $course */
                foreach ($selectedCourses as $course) {
                    $key = $student->getId() . '-' . $course->getId();
                    if (isset($usedPairs[$key])) {
                        continue;
                    }
                    $usedPairs[$key] = true;

                    EnrollmentFactory::createOne([
                        'student' => $student,
                        'course' => $course,
                        'paidPriceInCents' => $course->getPriceInCents(),
                    ]);
                }
            }
        });

        // Reviews : étudiants à 50%+ laissent un avis (60% de chance)
        /** @var array<string, true> $reviewedPairs */
        $reviewedPairs = [];
        flush_after(function () use ($students, &$reviewedPairs): void {
            /** @var Student $student */
            foreach ($students as $student) {
                foreach ($student->getEnrollments() as $enrollment) {
                    if ($enrollment->getProgressPercent() < 50) {
                        continue;
                    }
                    if (faker()->boolean(60) === false) {
                        continue;
                    }

                    /** @var Course $course */
                    $course = $enrollment->getCourse();
                    $key = $student->getId() . '-' . $course->getId();
                    if (isset($reviewedPairs[$key])) {
                        continue;
                    }
                    $reviewedPairs[$key] = true;

                    ReviewFactory::createOne([
                        'student' => $student,
                        'course' => $course,
                    ]);
                }
            }
        });
    }
}
