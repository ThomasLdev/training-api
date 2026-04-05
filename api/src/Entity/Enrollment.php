<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\UniqueConstraint(name: 'unique_enrollment', columns: ['student_id', 'course_id'])]
class Enrollment
{
    public const string STATUS_ACTIVE = 'active';
    public const string STATUS_COMPLETED = 'completed';
    public const string STATUS_CANCELLED = 'cancelled';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: 'uuid', unique: true)]
    private Uuid $uuid;

    #[ORM\ManyToOne(targetEntity: Student::class, inversedBy: 'enrollments')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Student $student = null;

    #[ORM\ManyToOne(targetEntity: Course::class, inversedBy: 'enrollments')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Course $course = null;

    #[ORM\Column]
    private \DateTimeImmutable $enrolledAt;

    #[ORM\Column]
    private int $progressPercent = 0;

    #[ORM\Column(nullable: true)]
    private ?float $finalGrade = null;

    #[ORM\Column(length: 20)]
    private string $status = self::STATUS_ACTIVE;

    #[ORM\Column]
    private int $paidPriceInCents = 0;

    public function __construct()
    {
        $this->uuid = Uuid::v7();
        $this->enrolledAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUuid(): Uuid
    {
        return $this->uuid;
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

    public function hasPaidFullPrice(): bool
    {
        return $this->paidPriceInCents < $this->course->getPriceInCents();
    }
}
