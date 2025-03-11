<?php

namespace App\Entity;

use App\Repository\PortfolioRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity(repositoryClass=PortfolioRepository::class)
 */
class Portfolio
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     * @Groups({"portfolio:read"})
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=100)
     * @Groups({"portfolio:read"})
     */
    private $name;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="portfolios")
     * @ORM\JoinColumn(nullable=false)
     */
    private $user;

    /**
     * @ORM\Column(type="datetime")
     * @Groups({"portfolio:read"})
     */
    private $createdAt;

    /**
     * @ORM\Column(type="text", nullable=true)
     * @Groups({"portfolio:read"})
     */
    private $description;

    /**
     * @ORM\OneToMany(targetEntity=PortfolioItem::class, mappedBy="portfolio", orphanRemoval=true)
     * @Groups({"portfolio:read"})
     */
    private $items;

    /**
     * @ORM\Column(type="decimal", precision=16, scale=2)
     * @Groups({"portfolio:read"})
     */
    private $totalValue;

    /**
     * @ORM\Column(type="decimal", precision=16, scale=2)
     * @Groups({"portfolio:read"})
     */
    private $totalCost;

    /**
     * @ORM\Column(type="decimal", precision=7, scale=4)
     * @Groups({"portfolio:read"})
     */
    private $performancePercent;

    /**
     * @ORM\Column(type="boolean")
     * @Groups({"portfolio:read"})
     */
    private $isDefault = false;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->items = new ArrayCollection();
        $this->totalValue = 0;
        $this->totalCost = 0;
        $this->performancePercent = 0;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return Collection<int, PortfolioItem>
     */
    public function getItems(): Collection
    {
        return $this->items;
    }

    public function addItem(PortfolioItem $item): self
    {
        if (!$this->items->contains($item)) {
            $this->items[] = $item;
            $item->setPortfolio($this);
            $this->recalculatePortfolioValue();
        }

        return $this;
    }

    public function removeItem(PortfolioItem $item): self
    {
        if ($this->items->removeElement($item)) {
            // set the owning side to null (unless already changed)
            if ($item->getPortfolio() === $this) {
                $item->setPortfolio(null);
            }
            $this->recalculatePortfolioValue();
        }

        return $this;
    }

    public function getTotalValue(): ?string
    {
        return $this->totalValue;
    }

    public function setTotalValue(string $totalValue): self
    {
        $this->totalValue = $totalValue;

        return $this;
    }

    public function getTotalCost(): ?string
    {
        return $this->totalCost;
    }

    public function setTotalCost(string $totalCost): self
    {
        $this->totalCost = $totalCost;

        return $this;
    }

    public function getPerformancePercent(): ?string
    {
        return $this->performancePercent;
    }

    public function setPerformancePercent(string $performancePercent): self
    {
        $this->performancePercent = $performancePercent;

        return $this;
    }

    public function isIsDefault(): ?bool
    {
        return $this->isDefault;
    }

    public function setIsDefault(bool $isDefault): self
    {
        $this->isDefault = $isDefault;

        return $this;
    }

    /**
     * Recalculate portfolio values
     */
    public function recalculatePortfolioValue(): void
    {
        $totalValue = 0;
        $totalCost = 0;
        $performancePercent = 0;

        foreach ($this->items as $item) {
            $totalValue += $item->getCurrentValue();
            $totalCost += $item->getTotalCost();
        }

        if ($totalCost > 0) {
            $performancePercent = (($totalValue - $totalCost) / $totalCost) * 100;
        }

        $this->setTotalValue((string) $totalValue);
        $this->setTotalCost((string) $totalCost);
        $this->setPerformancePercent((string) $performancePercent);
    }
} 