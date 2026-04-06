<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
class Course implements TimestampableInterface
{
    use TimestampableTrait;
    public const string STATUS_DRAFT = 'draft';
    public const string STATUS_PUBLISHED = 'published';
    public const string STATUS_ARCHIVED = 'archived';

    public const string LEVEL_BEGINNER = 'beginner';
    public const string LEVEL_INTERMEDIATE = 'intermediate';
    public const string LEVEL_ADVANCED = 'advanced';

    public const int MAX_LIST_EXCERPT_SIZE = 50;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: 'uuid', unique: true)]
    private Uuid $uuid;

    #[ORM\Column(length: 255)]
    private string $title = '';

    #[ORM\Column(type: Types::TEXT)]
    private string $description = '';

    #[ORM\Column(length: 20)]
    private string $level = self::LEVEL_BEGINNER;

    #[ORM\Column]
    private int $priceInCents = 0;

    #[ORM\Column]
    private int $maxStudents = 30;

    #[ORM\Column(length: 20)]
    private string $status = self::STATUS_DRAFT;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $publishedAt = null;

    #[ORM\ManyToOne(targetEntity: Instructor::class, inversedBy: 'courses')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Instructor $instructor = null;

    /** @var Collection<int, Module> $modules */
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
        $this->uuid = Uuid::v7();
        $this->modules = new ArrayCollection();
        $this->enrollments = new ArrayCollection();
        $this->reviews = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUuid(): Uuid
    {
        return $this->uuid;
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

    public function getExcerpt(): string
    {
        if (count_chars($this->description) <= self::MAX_LIST_EXCERPT_SIZE) {
            return $this->description;
        }

        return mb_substr($this->description, 0, self::MAX_LIST_EXCERPT_SIZE, 'UTF-8') . '...';
    }
}
