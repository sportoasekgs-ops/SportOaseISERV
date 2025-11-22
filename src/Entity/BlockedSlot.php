<?php

namespace SportOase\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'sportoase_blocked_slots')]
#[ORM\UniqueConstraint(name: 'unique_blocked_date_period', columns: ['date', 'period'])]
#[ORM\Index(columns: ['date'], name: 'idx_blocked_date')]
class BlockedSlot
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

    #[ORM\Column(type: 'string', length: 255)]
    private string $reason = 'Beratung';

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'blockedSlots')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $blockedBy;

    #[ORM\Column(type: 'datetime')]
    private \DateTime $createdAt;

    #[ORM\Column(type: 'datetime')]
    private \DateTime $updatedAt;

    public function __construct()
    {
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

    public function getReason(): string
    {
        return $this->reason;
    }

    public function setReason(string $reason): self
    {
        $this->reason = $reason;
        return $this;
    }

    public function getBlockedBy(): User
    {
        return $this->blockedBy;
    }

    public function setBlockedBy(User $blockedBy): self
    {
        $this->blockedBy = $blockedBy;
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
}
