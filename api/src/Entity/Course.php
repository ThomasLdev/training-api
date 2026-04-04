<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource]
#[ORM\Entity]
class Course
{
    public const string STATUS_DRAFT = 'draft';
    public const string STATUS_PUBLISHED = 'published';
    public const string STATUS_ARCHIVED = 'archived';

    public const string LEVEL_BEGINNER = 'beginner';
    public const string LEVEL_INTERMEDIATE = 'intermediate';
    public const string LEVEL_ADVANCED = 'advanced';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 3, max: 255)]
    private string $title = '';

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::TEXT)]
    #[Assert\NotBlank]
    private string $description = '';

    #[ORM\Column(length: 20)]
    #[Assert\Choice(choices: [self::LEVEL_BEGINNER, self::LEVEL_INTERMEDIATE, self::LEVEL_ADVANCED])]
    private string $level = self::LEVEL_BEGINNER;

    /** Prix en centimes */
    #[ORM\Column]
    #[Assert\PositiveOrZero]
    private int $priceInCents = 0;

    #[ORM\Column]
    #[Assert\Positive]
    private int $maxStudents = 30;

    #[ORM\Column(length: 20)]
    #[Assert\Choice(choices: [self::STATUS_DRAFT, self::STATUS_PUBLISHED, self::STATUS_ARCHIVED])]
    private string $status = self::STATUS_DRAFT;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $publishedAt = null;

    #[ORM\ManyToOne(targetEntity: Instructor::class, inversedBy: 'courses')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull]
    private ?Instructor $instructor = null;

    /** @var Collection<int, Module> */
    #[ORM\OneToMany(
        targetEntity: Module::class,
        mappedBy: 'course',
        cascade: ['persist', 'remove'],
        orphanRemoval: true,
    )]
    #[ORM\OrderBy(['position' => 'ASC'])]
    private Collection $modules;

    /** @var Collection<int, Enrollment> */
    #[ORM\OneToMany(targetEntity: Enrollment::class, mappedBy: 'course', orphanRemoval: true)]
    private Collection $enrollments;

    /** @var Collection<int, Review> */
    #[ORM\OneToMany(targetEntity: Review::class, mappedBy: 'course', orphanRemoval: true)]
    private Collection $reviews;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->modules = new ArrayCollection();
        $this->enrollments = new ArrayCollection();
        $this->reviews = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;
        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getLevel(): string
    {
        return $this->level;
    }

    public function setLevel(string $level): static
    {
        $this->level = $level;
        return $this;
    }

    public function getPriceInCents(): int
    {
        return $this->priceInCents;
    }

    public function setPriceInCents(int $priceInCents): static
    {
        $this->priceInCents = $priceInCents;
        return $this;
    }

    public function getMaxStudents(): int
    {
        return $this->maxStudents;
    }

    public function setMaxStudents(int $maxStudents): static
    {
        $this->maxStudents = $maxStudents;
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

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getPublishedAt(): ?\DateTimeImmutable
    {
        return $this->publishedAt;
    }

    public function setPublishedAt(?\DateTimeImmutable $publishedAt): static
    {
        $this->publishedAt = $publishedAt;
        return $this;
    }

    public function getInstructor(): ?Instructor
    {
        return $this->instructor;
    }

    public function setInstructor(?Instructor $instructor): static
    {
        $this->instructor = $instructor;
        return $this;
    }

    /** @return Collection<int, Module> */
    public function getModules(): Collection
    {
        return $this->modules;
    }

    public function addModule(Module $module): static
    {
        if (!$this->modules->contains($module)) {
            $this->modules->add($module);
            $module->setCourse($this);
        }
        return $this;
    }

    public function removeModule(Module $module): static
    {
        $this->modules->removeElement($module);
        return $this;
    }

    /** @return Collection<int, Enrollment> */
    public function getEnrollments(): Collection
    {
        return $this->enrollments;
    }

    public function addEnrollment(Enrollment $enrollment): static
    {
        if (!$this->enrollments->contains($enrollment)) {
            $this->enrollments->add($enrollment);
            $enrollment->setCourse($this);
        }
        return $this;
    }

    public function removeEnrollment(Enrollment $enrollment): static
    {
        $this->enrollments->removeElement($enrollment);
        return $this;
    }

    /** @return Collection<int, Review> */
    public function getReviews(): Collection
    {
        return $this->reviews;
    }

    public function addReview(Review $review): static
    {
        if (!$this->reviews->contains($review)) {
            $this->reviews->add($review);
            $review->setCourse($this);
        }
        return $this;
    }

    public function removeReview(Review $review): static
    {
        $this->reviews->removeElement($review);
        return $this;
    }

    public function getStudentCount(): int
    {
        return $this->enrollments->count();
    }

    public function getAverageRating(): ?float
    {
        if ($this->reviews->isEmpty()) {
            return null;
        }

        $total = 0;
        foreach ($this->reviews as $review) {
            $total += $review->getRating();
        }

        return round($total / $this->reviews->count(), 2);
    }

    public function getAvailableSpots(): int
    {
        return max(0, $this->maxStudents - $this->enrollments->count());
    }
}
