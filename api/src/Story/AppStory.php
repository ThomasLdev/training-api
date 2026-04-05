<?php

declare(strict_types=1);

namespace App\Story;

use App\Entity\Course;
use App\Entity\Enrollment;
use App\Entity\Instructor;
use App\Entity\Student;
use App\Factory\CourseFactory;
use App\Factory\EnrollmentFactory;
use App\Factory\InstructorFactory;
use App\Factory\ModuleFactory;
use App\Factory\ReviewFactory;
use App\Factory\StudentFactory;
use Zenstruck\Foundry\Attribute\AsFixture;
use Zenstruck\Foundry\Story;

use function Zenstruck\Foundry\faker;
use function Zenstruck\Foundry\Persistence\flush_after;

#[AsFixture(name: 'main')]
final class AppStory extends Story
{
    public function build(): void
    {
        // 30 instructeurs + 200 cours publiés + drafts/archivés
        /** @var list<Instructor> $instructors */
        $instructors = flush_after(fn (): array => InstructorFactory::createMany(30));

        /** @var list<Course> $publishedCourses */
        $publishedCourses = flush_after(fn (): array => CourseFactory::createMany(200, fn (): array => [
            'instructor' => faker()->randomElement($instructors),
        ]));

        flush_after(function () use ($instructors): void {
            foreach (faker()->randomElements($instructors, 20) as $instructor) {
                CourseFactory::new()->draft()->create(['instructor' => $instructor]);
            }
            foreach (faker()->randomElements($instructors, 10) as $instructor) {
                CourseFactory::new()->archived()->create(['instructor' => $instructor]);
            }
        });

        // 3-8 modules par cours publié
        flush_after(function () use ($publishedCourses): void {
            foreach ($publishedCourses as $course) {
                $moduleCount = faker()->numberBetween(3, 8);
                for ($i = 0; $i < $moduleCount; $i++) {
                    ModuleFactory::createOne([
                        'course' => $course,
                        'position' => $i,
                    ]);
                }
            }
        });

        // 2000 étudiants
        /** @var list<Student> $students */
        $students = flush_after(fn (): array => StudentFactory::createMany(2000));

        // Chaque étudiant inscrit à 1-6 cours — batch par groupes de 200
        /** @var array<string, true> $usedPairs */
        $usedPairs = [];
        foreach (array_chunk($students, 200) as $batch) {
            flush_after(function () use ($batch, $publishedCourses, &$usedPairs): void {
                /** @var Student $student */
                foreach ($batch as $student) {
                    $courseCount = faker()->numberBetween(1, 6);
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
        }

        // Reviews : étudiants à 50%+ laissent un avis (60% de chance) — batch par groupes de 200
        /** @var array<string, true> $reviewedPairs */
        $reviewedPairs = [];
        foreach (array_chunk($students, 200) as $batch) {
            flush_after(function () use ($batch, &$reviewedPairs): void {
                /** @var Student $student */
                foreach ($batch as $student) {
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
}
