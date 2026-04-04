<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\UniqueConstraint(name: 'unique_enrollment', columns: ['student_id', 'course_id'])]
#[UniqueEntity(fields: ['student', 'course'], message: 'This student is already enrolled in this course.')]
class Enrollment
{
    public const STATUS_ACTIVE = 'active';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Student::class, inversedBy: 'enrollments')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull]
    private ?Student $student = null;

    #[ORM\ManyToOne(targetEntity: Course::class, inversedBy: 'enrollments')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull]
    private ?Course $course = null;

    #[ORM\Column]
    private \DateTimeImmutable $enrolledAt;

    /** Progression en pourcentage (0-100) */
    #[ORM\Column]
    #[Assert\Range(min: 0, max: 100)]
    private int $progressPercent = 0;

    /** Note finale (0-20), null si pas encore complété */
    #[ORM\Column(nullable: true)]
    #[Assert\Range(min: 0, max: 20)]
    private ?float $finalGrade = null;

    #[ORM\Column(length: 20)]
    #[Assert\Choice(choices: [self::STATUS_ACTIVE, self::STATUS_COMPLETED, self::STATUS_CANCELLED])]
    private string $status = self::STATUS_ACTIVE;

    /** Prix payé en centimes (peut différer du prix du cours si promo) */
    #[ORM\Column]
    #[Assert\PositiveOrZero]
    private int $paidPriceInCents = 0;

    public function __construct()
    {
        $this->enrolledAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStudent(): ?Student
    {
        return $this->student;
    }

    public function setStudent(?Student $student): static
    {
        $this->student = $student;
        return $this;
    }

    public function getCourse(): ?Course
    {
        return $this->course;
    }

    public function setCourse(?Course $course): static
    {
        $this->course = $course;
        return $this;
    }

    public function getEnrolledAt(): \DateTimeImmutable
    {
        return $this->enrolledAt;
    }

    public function getProgressPercent(): int
    {
        return $this->progressPercent;
    }

    public function setProgressPercent(int $progressPercent): static
    {
        $this->progressPercent = $progressPercent;
        return $this;
    }

    public function getFinalGrade(): ?float
    {
        return $this->finalGrade;
    }

    public function setFinalGrade(?float $finalGrade): static
    {
        $this->finalGrade = $finalGrade;
        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;
        return $this;
    }

    public function getPaidPriceInCents(): int
    {
        return $this->paidPriceInCents;
    }

    public function setPaidPriceInCents(int $paidPriceInCents): static
    {
        $this->paidPriceInCents = $paidPriceInCents;
        return $this;
    }
}
