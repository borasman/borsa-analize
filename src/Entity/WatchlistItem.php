<?php

namespace App\Entity;

use App\Repository\WatchlistItemRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity(repositoryClass=WatchlistItemRepository::class)
 */
class WatchlistItem
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     * @Groups({"watchlist:read"})
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=Watchlist::class, inversedBy="items")
     * @ORM\JoinColumn(nullable=false)
     */
    private $watchlist;

    /**
     * @ORM\ManyToOne(targetEntity=Stock::class, inversedBy="watchlistItems")
     * @ORM\JoinColumn(nullable=false)
     * @Groups({"watchlist:read"})
     */
    private $stock;

    /**
     * @ORM\Column(type="datetime")
     * @Groups({"watchlist:read"})
     */
    private $addedAt;

    /**
     * @ORM\Column(type="decimal", precision=14, scale=4, nullable=true)
     * @Groups({"watchlist:read"})
     */
    private $alertAbovePrice;

    /**
     * @ORM\Column(type="decimal", precision=14, scale=4, nullable=true)
     * @Groups({"watchlist:read"})
     */
    private $alertBelowPrice;

    /**
     * @ORM\Column(type="decimal", precision=7, scale=4, nullable=true)
     * @Groups({"watchlist:read"})
     */
    private $alertPercentChange;

    /**
     * @ORM\Column(type="string", length=10, nullable=true)
     * @Groups({"watchlist:read"})
     */
    private $notes;

    /**
     * @ORM\Column(type="integer")
     * @Groups({"watchlist:read"})
     */
    private $sortOrder = 0;

    public function __construct()
    {
        $this->addedAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getWatchlist(): ?Watchlist
    {
        return $this->watchlist;
    }

    public function setWatchlist(?Watchlist $watchlist): self
    {
        $this->watchlist = $watchlist;

        return $this;
    }

    public function getStock(): ?Stock
    {
        return $this->stock;
    }

    public function setStock(?Stock $stock): self
    {
        $this->stock = $stock;

        return $this;
    }

    public function getAddedAt(): ?\DateTimeInterface
    {
        return $this->addedAt;
    }

    public function setAddedAt(\DateTimeInterface $addedAt): self
    {
        $this->addedAt = $addedAt;

        return $this;
    }

    public function getAlertAbovePrice(): ?string
    {
        return $this->alertAbovePrice;
    }

    public function setAlertAbovePrice(?string $alertAbovePrice): self
    {
        $this->alertAbovePrice = $alertAbovePrice;

        return $this;
    }

    public function getAlertBelowPrice(): ?string
    {
        return $this->alertBelowPrice;
    }

    public function setAlertBelowPrice(?string $alertBelowPrice): self
    {
        $this->alertBelowPrice = $alertBelowPrice;

        return $this;
    }

    public function getAlertPercentChange(): ?string
    {
        return $this->alertPercentChange;
    }

    public function setAlertPercentChange(?string $alertPercentChange): self
    {
        $this->alertPercentChange = $alertPercentChange;

        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): self
    {
        $this->notes = $notes;

        return $this;
    }

    public function getSortOrder(): ?int
    {
        return $this->sortOrder;
    }

    public function setSortOrder(int $sortOrder): self
    {
        $this->sortOrder = $sortOrder;

        return $this;
    }

    /**
     * Check if any price alerts are set
     */
    public function hasAlerts(): bool
    {
        return $this->alertAbovePrice !== null 
            || $this->alertBelowPrice !== null 
            || $this->alertPercentChange !== null;
    }

    /**
     * Check if current price triggers any alerts
     */
    public function checkAlerts(): bool
    {
        if (!$this->stock) {
            return false;
        }

        $currentPrice = (float) $this->stock->getCurrentPrice();
        $previousClose = (float) $this->stock->getPreviousClose();

        // Check above price alert
        if ($this->alertAbovePrice !== null && $currentPrice > (float) $this->alertAbovePrice) {
            return true;
        }

        // Check below price alert
        if ($this->alertBelowPrice !== null && $currentPrice < (float) $this->alertBelowPrice) {
            return true;
        }

        // Check percent change alert
        if ($this->alertPercentChange !== null && $previousClose > 0) {
            $percentChange = ($currentPrice - $previousClose) / $previousClose * 100;
            $absPercentChange = abs($percentChange);
            $absAlertPercent = abs((float) $this->alertPercentChange);
            
            if ($absPercentChange >= $absAlertPercent) {
                return true;
            }
        }

        return false;
    }
} 