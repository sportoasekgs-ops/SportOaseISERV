<?php

namespace SportOase\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'sportoase_fixed_offer_names')]
class FixedOfferName
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 100, unique: true)]
    private string $offerKey;

    #[ORM\Column(type: 'string', length: 255)]
    private string $defaultName;

    #[ORM\Column(type: 'string', length: 255)]
    private string $customName;

    #[ORM\Column(type: 'datetime')]
    private \DateTime $updatedAt;

    public function __construct()
    {
        $this->updatedAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOfferKey(): string
    {
        return $this->offerKey;
    }

    public function setOfferKey(string $offerKey): self
    {
        $this->offerKey = $offerKey;
        return $this;
    }

    public function getDefaultName(): string
    {
        return $this->defaultName;
    }

    public function setDefaultName(string $defaultName): self
    {
        $this->defaultName = $defaultName;
        return $this;
    }

    public function getCustomName(): string
    {
        return $this->customName;
    }

    public function setCustomName(string $customName): self
    {
        $this->customName = $customName;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function getUpdatedAt(): \DateTime
    {
        return $this->updatedAt;
    }
}
