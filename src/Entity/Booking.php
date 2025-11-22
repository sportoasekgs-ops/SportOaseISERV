<?php

namespace SportOase\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

#[ORM\Entity]
#[ORM\Table(name: 'sportoase_bookings')]
#[ORM\UniqueConstraint(name: 'unique_date_period', columns: ['date', 'period'])]
#[ORM\Index(columns: ['date', 'period'], name: 'idx_date_period')]
#[ORM\Index(columns: ['date'], name: 'idx_date')]
class Booking
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'date')]
    private \DateTime $date;

    #[ORM\Column(type: 'integer')]
    private int $period;

    #[ORM\Column(type: 'string', length: 20)]
    private string $weekday;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'bookings')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $teacher;

    #[ORM\Column(type: 'string', length: 255)]
    private string $teacherName;

    #[ORM\Column(type: 'string', length: 255)]
    private string $teacherClass;

    #[ORM\Column(type: 'json')]
    private array $studentsJson = [];

    #[ORM\Column(type: 'string', length: 100)]
    private string $offerType;

    #[ORM\Column(type: 'string', length: 255)]
    private string $offerLabel;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $calendarEventId = null;

    #[ORM\Column(type: 'datetime')]
    private \DateTime $createdAt;

    #[ORM\Column(type: 'datetime')]
    private \DateTime $updatedAt;

    #[ORM\OneToMany(targetEntity: Notification::class, mappedBy: 'booking', cascade: ['remove'], orphanRemoval: true)]
    private Collection $notifications;

    public function __construct()
    {
        $this->notifications = new ArrayCollection();
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDate(): \DateTime
    {
        return $this->date;
    }

    public function setDate(\DateTime $date): self
    {
        $this->date = $date;
        return $this;
    }

    public function getPeriod(): int
    {
        return $this->period;
    }

    public function setPeriod(int $period): self
    {
        $this->period = $period;
        return $this;
    }

    public function getWeekday(): string
    {
        return $this->weekday;
    }

    public function setWeekday(string $weekday): self
    {
        $this->weekday = $weekday;
        return $this;
    }

    public function getTeacher(): User
    {
        return $this->teacher;
    }

    public function setTeacher(User $teacher): self
    {
        $this->teacher = $teacher;
        return $this;
    }

    public function getTeacherName(): string
    {
        return $this->teacherName;
    }

    public function setTeacherName(string $teacherName): self
    {
        $this->teacherName = $teacherName;
        return $this;
    }

    public function getTeacherClass(): string
    {
        return $this->teacherClass;
    }

    public function setTeacherClass(string $teacherClass): self
    {
        $this->teacherClass = $teacherClass;
        return $this;
    }

    public function getStudentsJson(): array
    {
        return $this->studentsJson;
    }

    public function setStudentsJson(array $studentsJson): self
    {
        $this->studentsJson = $studentsJson;
        return $this;
    }

    public function getOfferType(): string
    {
        return $this->offerType;
    }

    public function setOfferType(string $offerType): self
    {
        $this->offerType = $offerType;
        return $this;
    }

    public function getOfferLabel(): string
    {
        return $this->offerLabel;
    }

    public function setOfferLabel(string $offerLabel): self
    {
        $this->offerLabel = $offerLabel;
        return $this;
    }

    public function getCalendarEventId(): ?string
    {
        return $this->calendarEventId;
    }

    public function setCalendarEventId(?string $calendarEventId): self
    {
        $this->calendarEventId = $calendarEventId;
        return $this;
    }

    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTime
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTime $updatedAt): self
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function getNotifications(): Collection
    {
        return $this->notifications;
    }
}
