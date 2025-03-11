<?php

namespace App\Entity;

use App\Repository\StockHistoricalDataRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity(repositoryClass=StockHistoricalDataRepository::class)
 * @ORM\Table(indexes={
 *     @ORM\Index(name="date_idx", columns={"date"})
 * })
 */
class StockHistoricalData
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     * @Groups({"stock_history:read"})
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=Stock::class, inversedBy="historicalData")
     * @ORM\JoinColumn(nullable=false)
     * @Groups({"stock_history:read"})
     */
    private $stock;

    /**
     * @ORM\Column(type="date")
     * @Groups({"stock_history:read"})
     */
    private $date;

    /**
     * @ORM\Column(type="decimal", precision=14, scale=4)
     * @Groups({"stock_history:read"})
     */
    private $openPrice;

    /**
     * @ORM\Column(type="decimal", precision=14, scale=4)
     * @Groups({"stock_history:read"})
     */
    private $highPrice;

    /**
     * @ORM\Column(type="decimal", precision=14, scale=4)
     * @Groups({"stock_history:read"})
     */
    private $lowPrice;

    /**
     * @ORM\Column(type="decimal", precision=14, scale=4)
     * @Groups({"stock_history:read"})
     */
    private $closePrice;

    /**
     * @ORM\Column(type="decimal", precision=20, scale=2)
     * @Groups({"stock_history:read"})
     */
    private $volume;

    /**
     * @ORM\Column(type="decimal", precision=14, scale=4, nullable=true)
     * @Groups({"stock_history:read"})
     */
    private $adjustedClose;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(\DateTimeInterface $date): self
    {
        $this->date = $date;

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

    public function getClosePrice(): ?string
    {
        return $this->closePrice;
    }

    public function setClosePrice(string $closePrice): self
    {
        $this->closePrice = $closePrice;

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

    public function getAdjustedClose(): ?string
    {
        return $this->adjustedClose;
    }

    public function setAdjustedClose(?string $adjustedClose): self
    {
        $this->adjustedClose = $adjustedClose;

        return $this;
    }
} 