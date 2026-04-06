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
use Symfony\Component\Uid\Uuid;
use Zenstruck\Foundry\Attribute\AsFixture;
use Zenstruck\Foundry\Story;

use function Zenstruck\Foundry\faker;
use function Zenstruck\Foundry\Persistence\flush_after;

#[AsFixture(name: 'main')]
final class AppStory extends Story
{
    // Stable UUIDs for test entities — never change between fixture reloads
    public const string INSTRUCTOR_UUID = '00000000-0000-7000-a000-000000000001';
    public const string STUDENT_UUID = '00000000-0000-7000-a000-000000000002';
    public const string COURSE_UUID = '00000000-0000-7000-a000-000000000003';
    public const string ENROLLMENT_UUID = '00000000-0000-7000-a000-000000000004';
    public const string REVIEW_UUID = '00000000-0000-7000-a000-000000000005';

    public function build(): void
    {
        // Known accounts (password: "password")
        /** @var list<Instructor> $instructors */
        $instructors = flush_after(function (): array {
            UserFactory::createOne(['email' => 'admin@training.local', 'roles' => ['ROLE_ADMIN']]);

            $instructors = InstructorFactory::createMany(10);

            $instructors[] = InstructorFactory::createOne([
                'uuid' => Uuid::fromString(self::INSTRUCTOR_UUID),
                'user' => UserFactory::new()->instructor()->with(['email' => 'instructor@training.local']),
                'firstName' => 'Marie',
                'lastName' => 'Dupont',
                'specialty' => 'PHP & Symfony',
            ]);

            return $instructors;
        });

        /** @var list<Course> $publishedCourses */
        $publishedCourses = flush_after(function () use ($instructors): array {
            $courses = CourseFactory::createMany(29, fn (): array => [
                'instructor' => faker()->randomElement($instructors),
            ]);

            $courses[] = CourseFactory::createOne([
                'uuid' => Uuid::fromString(self::COURSE_UUID),
                'instructor' => $instructors[array_key_last($instructors)],
            ]);

            return $courses;
        });

        flush_after(function () use ($instructors): void {
            foreach (faker()->randomElements($instructors, 5) as $instructor) {
                CourseFactory::new()->draft()->create(['instructor' => $instructor]);
            }
            foreach (faker()->randomElements($instructors, 3) as $instructor) {
                CourseFactory::new()->archived()->create(['instructor' => $instructor]);
            }
        });

        // 3-6 modules per published course
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

        // 100 students + 1 known
        $knownCourse = $publishedCourses[array_key_last($publishedCourses)];

        /** @var list<Student> $students */
        $students = flush_after(function () use ($knownCourse, &$knownStudent): array {
            $students = StudentFactory::createMany(100);

            $knownStudent = StudentFactory::createOne([
                'uuid' => Uuid::fromString(self::STUDENT_UUID),
                'user' => UserFactory::new()->student()->with(['email' => 'student@training.local']),
                'firstName' => 'Jean',
                'lastName' => 'Martin',
                'email' => 'student@training.local',
            ]);
            $students[] = $knownStudent;

            return $students;
        });

        // Enroll known student in known course with stable UUID
        flush_after(function () use ($knownStudent, $knownCourse): void {
            EnrollmentFactory::createOne([
                'uuid' => Uuid::fromString(self::ENROLLMENT_UUID),
                'student' => $knownStudent,
                'course' => $knownCourse,
                'paidPriceInCents' => $knownCourse->getPriceInCents(),
                'progressPercent' => 75,
            ]);

            ReviewFactory::createOne([
                'uuid' => Uuid::fromString(self::REVIEW_UUID),
                'student' => $knownStudent,
                'course' => $knownCourse,
            ]);
        });

        // Each student enrolled in 1-4 courses
        /** @var array<string, true> $usedPairs */
        $usedPairs = [$knownStudent->getId() . '-' . $knownCourse->getId() => true];
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

        // Reviews: students with 50%+ progress leave a review (60% chance)
        /** @var array<string, true> $reviewedPairs */
        $reviewedPairs = [$knownStudent->getId() . '-' . $knownCourse->getId() => true];
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
