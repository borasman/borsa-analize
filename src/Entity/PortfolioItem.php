<?php

namespace App\Entity;

use App\Repository\PortfolioItemRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity(repositoryClass=PortfolioItemRepository::class)
 */
class PortfolioItem
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     * @Groups({"portfolio:read"})
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=Portfolio::class, inversedBy="items")
     * @ORM\JoinColumn(nullable=false)
     */
    private $portfolio;

    /**
     * @ORM\ManyToOne(targetEntity=Stock::class, inversedBy="portfolioItems")
     * @ORM\JoinColumn(nullable=false)
     * @Groups({"portfolio:read"})
     */
    private $stock;

    /**
     * @ORM\Column(type="integer")
     * @Groups({"portfolio:read"})
     */
    private $quantity;

    /**
     * @ORM\Column(type="decimal", precision=14, scale=4)
     * @Groups({"portfolio:read"})
     */
    private $averageBuyPrice;

    /**
     * @ORM\Column(type="decimal", precision=16, scale=2)
     * @Groups({"portfolio:read"})
     */
    private $totalCost;

    /**
     * @ORM\Column(type="decimal", precision=16, scale=2)
     * @Groups({"portfolio:read"})
     */
    private $currentValue;

    /**
     * @ORM\Column(type="decimal", precision=7, scale=4)
     * @Groups({"portfolio:read"})
     */
    private $performancePercent;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     * @Groups({"portfolio:read"})
     */
    private $lastUpdated;

    public function __construct()
    {
        $this->lastUpdated = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPortfolio(): ?Portfolio
    {
        return $this->portfolio;
    }

    public function setPortfolio(?Portfolio $portfolio): self
    {
        $this->portfolio = $portfolio;

        return $this;
    }

    public function getStock(): ?Stock
    {
        return $this->stock;
    }

    public function setStock(?Stock $stock): self
    {
        $this->stock = $stock;
        $this->updateCurrentValue();

        return $this;
    }

    public function getQuantity(): ?int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): self
    {
        $this->quantity = $quantity;
        $this->updateCurrentValue();
        
        return $this;
    }

    public function getAverageBuyPrice(): ?string
    {
        return $this->averageBuyPrice;
    }

    public function setAverageBuyPrice(string $averageBuyPrice): self
    {
        $this->averageBuyPrice = $averageBuyPrice;
        $this->updateTotalCost();
        
        return $this;
    }

    public function getTotalCost(): ?string
    {
        return $this->totalCost;
    }

    public function setTotalCost(string $totalCost): self
    {
        $this->totalCost = $totalCost;
        $this->updatePerformance();
        
        return $this;
    }

    public function getCurrentValue(): ?string
    {
        return $this->currentValue;
    }

    public function setCurrentValue(string $currentValue): self
    {
        $this->currentValue = $currentValue;
        $this->updatePerformance();
        
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

    public function getLastUpdated(): ?\DateTimeInterface
    {
        return $this->lastUpdated;
    }

    public function setLastUpdated(?\DateTimeInterface $lastUpdated): self
    {
        $this->lastUpdated = $lastUpdated;
        
        return $this;
    }

    /**
     * Updates the current value based on stock price and quantity
     */
    public function updateCurrentValue(): void
    {
        if ($this->stock && $this->quantity) {
            $currentPrice = $this->stock->getCurrentPrice();
            $this->currentValue = (string) ((float) $currentPrice * $this->quantity);
            $this->lastUpdated = new \DateTime();
            $this->updatePerformance();
        }
    }

    /**
     * Updates total cost based on average buy price and quantity
     */
    private function updateTotalCost(): void
    {
        if ($this->averageBuyPrice && $this->quantity) {
            $this->totalCost = (string) ((float) $this->averageBuyPrice * $this->quantity);
            $this->updatePerformance();
        }
    }

    /**
     * Updates performance percentage
     */
    private function updatePerformance(): void
    {
        if ($this->totalCost && (float) $this->totalCost > 0 && $this->currentValue) {
            $performancePercent = ((float) $this->currentValue - (float) $this->totalCost) / (float) $this->totalCost * 100;
            $this->performancePercent = (string) $performancePercent;
        } else {
            $this->performancePercent = '0';
        }
    }
} 