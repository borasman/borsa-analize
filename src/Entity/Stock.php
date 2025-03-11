<?php

namespace App\Entity;

use App\Repository\StockRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity(repositoryClass=StockRepository::class)
 */
class Stock
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     * @Groups({"stock:read", "portfolio:read"})
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=10, unique=true)
     * @Groups({"stock:read", "portfolio:read", "transaction:read"})
     */
    private $symbol;

    /**
     * @ORM\Column(type="string", length=100)
     * @Groups({"stock:read", "portfolio:read", "transaction:read"})
     */
    private $name;

    /**
     * @ORM\Column(type="string", length=100, nullable=true)
     * @Groups({"stock:read"})
     */
    private $sector;

    /**
     * @ORM\Column(type="text", nullable=true)
     * @Groups({"stock:read"})
     */
    private $description;

    /**
     * @ORM\Column(type="decimal", precision=14, scale=4)
     * @Groups({"stock:read", "portfolio:read", "transaction:read"})
     */
    private $currentPrice;

    /**
     * @ORM\Column(type="decimal", precision=14, scale=4)
     * @Groups({"stock:read"})
     */
    private $previousClose;

    /**
     * @ORM\Column(type="decimal", precision=14, scale=4)
     * @Groups({"stock:read"})
     */
    private $openPrice;

    /**
     * @ORM\Column(type="decimal", precision=14, scale=4)
     * @Groups({"stock:read"})
     */
    private $highPrice;

    /**
     * @ORM\Column(type="decimal", precision=14, scale=4)
     * @Groups({"stock:read"})
     */
    private $lowPrice;

    /**
     * @ORM\Column(type="decimal", precision=14, scale=4)
     * @Groups({"stock:read"})
     */
    private $dayChange;

    /**
     * @ORM\Column(type="decimal", precision=10, scale=4)
     * @Groups({"stock:read", "portfolio:read"})
     */
    private $dayChangePercent;

    /**
     * @ORM\Column(type="decimal", precision=20, scale=2)
     * @Groups({"stock:read"})
     */
    private $volume;

    /**
     * @ORM\Column(type="decimal", precision=20, scale=2)
     * @Groups({"stock:read"})
     */
    private $marketCap;

    /**
     * @ORM\Column(type="decimal", precision=10, scale=4, nullable=true)
     * @Groups({"stock:read"})
     */
    private $pe;

    /**
     * @ORM\Column(type="decimal", precision=10, scale=4, nullable=true)
     * @Groups({"stock:read"})
     */
    private $dividendYield;

    /**
     * @ORM\Column(type="datetime")
     * @Groups({"stock:read"})
     */
    private $lastUpdated;

    /**
     * @ORM\OneToMany(targetEntity=StockHistoricalData::class, mappedBy="stock", orphanRemoval=true)
     */
    private $historicalData;

    /**
     * @ORM\OneToMany(targetEntity=PortfolioItem::class, mappedBy="stock")
     */
    private $portfolioItems;

    /**
     * @ORM\OneToMany(targetEntity=WatchlistItem::class, mappedBy="stock", orphanRemoval=true)
     */
    private $watchlistItems;

    /**
     * @ORM\OneToMany(targetEntity=Transaction::class, mappedBy="stock")
     */
    private $transactions;

    public function __construct()
    {
        $this->lastUpdated = new \DateTime();
        $this->historicalData = new ArrayCollection();
        $this->portfolioItems = new ArrayCollection();
        $this->watchlistItems = new ArrayCollection();
        $this->transactions = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSymbol(): ?string
    {
        return $this->symbol;
    }

    public function setSymbol(string $symbol): self
    {
        $this->symbol = $symbol;

        return $this;
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

    public function getSector(): ?string
    {
        return $this->sector;
    }

    public function setSector(?string $sector): self
    {
        $this->sector = $sector;

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

    public function getCurrentPrice(): ?string
    {
        return $this->currentPrice;
    }

    public function setCurrentPrice(string $currentPrice): self
    {
        $this->currentPrice = $currentPrice;

        return $this;
    }

    public function getPreviousClose(): ?string
    {
        return $this->previousClose;
    }

    public function setPreviousClose(string $previousClose): self
    {
        $this->previousClose = $previousClose;

        return $this;
    }

    public function getOpenPrice(): ?string
    {
        return $this->openPrice;
    }

    public function setOpenPrice(string $openPrice): self
    {
        $this->openPrice = $openPrice;

        return $this;
    }

    public function getHighPrice(): ?string
    {
        return $this->highPrice;
    }

    public function setHighPrice(string $highPrice): self
    {
        $this->highPrice = $highPrice;

        return $this;
    }

    public function getLowPrice(): ?string
    {
        return $this->lowPrice;
    }

    public function setLowPrice(string $lowPrice): self
    {
        $this->lowPrice = $lowPrice;

        return $this;
    }

    public function getDayChange(): ?string
    {
        return $this->dayChange;
    }

    public function setDayChange(string $dayChange): self
    {
        $this->dayChange = $dayChange;

        return $this;
    }

    public function getDayChangePercent(): ?string
    {
        return $this->dayChangePercent;
    }

    public function setDayChangePercent(string $dayChangePercent): self
    {
        $this->dayChangePercent = $dayChangePercent;

        return $this;
    }

    public function getVolume(): ?string
    {
        return $this->volume;
    }

    public function setVolume(string $volume): self
    {
        $this->volume = $volume;

        return $this;
    }

    public function getMarketCap(): ?string
    {
        return $this->marketCap;
    }

    public function setMarketCap(string $marketCap): self
    {
        $this->marketCap = $marketCap;

        return $this;
    }

    public function getPe(): ?string
    {
        return $this->pe;
    }

    public function setPe(?string $pe): self
    {
        $this->pe = $pe;

        return $this;
    }

    public function getDividendYield(): ?string
    {
        return $this->dividendYield;
    }

    public function setDividendYield(?string $dividendYield): self
    {
        $this->dividendYield = $dividendYield;

        return $this;
    }

    public function getLastUpdated(): ?\DateTimeInterface
    {
        return $this->lastUpdated;
    }

    public function setLastUpdated(\DateTimeInterface $lastUpdated): self
    {
        $this->lastUpdated = $lastUpdated;

        return $this;
    }

    /**
     * @return Collection<int, StockHistoricalData>
     */
    public function getHistoricalData(): Collection
    {
        return $this->historicalData;
    }

    public function addHistoricalData(StockHistoricalData $historicalData): self
    {
        if (!$this->historicalData->contains($historicalData)) {
            $this->historicalData[] = $historicalData;
            $historicalData->setStock($this);
        }

        return $this;
    }

    public function removeHistoricalData(StockHistoricalData $historicalData): self
    {
        if ($this->historicalData->removeElement($historicalData)) {
            // set the owning side to null (unless already changed)
            if ($historicalData->getStock() === $this) {
                $historicalData->setStock(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, PortfolioItem>
     */
    public function getPortfolioItems(): Collection
    {
        return $this->portfolioItems;
    }

    public function addPortfolioItem(PortfolioItem $portfolioItem): self
    {
        if (!$this->portfolioItems->contains($portfolioItem)) {
            $this->portfolioItems[] = $portfolioItem;
            $portfolioItem->setStock($this);
        }

        return $this;
    }

    public function removePortfolioItem(PortfolioItem $portfolioItem): self
    {
        if ($this->portfolioItems->removeElement($portfolioItem)) {
            // set the owning side to null (unless already changed)
            if ($portfolioItem->getStock() === $this) {
                $portfolioItem->setStock(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, WatchlistItem>
     */
    public function getWatchlistItems(): Collection
    {
        return $this->watchlistItems;
    }

    public function addWatchlistItem(WatchlistItem $watchlistItem): self
    {
        if (!$this->watchlistItems->contains($watchlistItem)) {
            $this->watchlistItems[] = $watchlistItem;
            $watchlistItem->setStock($this);
        }

        return $this;
    }

    public function removeWatchlistItem(WatchlistItem $watchlistItem): self
    {
        if ($this->watchlistItems->removeElement($watchlistItem)) {
            // set the owning side to null (unless already changed)
            if ($watchlistItem->getStock() === $this) {
                $watchlistItem->setStock(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Transaction>
     */
    public function getTransactions(): Collection
    {
        return $this->transactions;
    }

    public function addTransaction(Transaction $transaction): self
    {
        if (!$this->transactions->contains($transaction)) {
            $this->transactions[] = $transaction;
            $transaction->setStock($this);
        }

        return $this;
    }

    public function removeTransaction(Transaction $transaction): self
    {
        if ($this->transactions->removeElement($transaction)) {
            // set the owning side to null (unless already changed)
            if ($transaction->getStock() === $this) {
                $transaction->setStock(null);
            }
        }

        return $this;
    }
} 