<?php

namespace App\Entity;

use App\Repository\TransactionRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity(repositoryClass=TransactionRepository::class)
 * @ORM\Table(indexes={
 *     @ORM\Index(name="transaction_date_idx", columns={"transaction_date"})
 * })
 */
class Transaction
{
    const TYPE_BUY = 'buy';
    const TYPE_SELL = 'sell';
    
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     * @Groups({"transaction:read"})
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="transactions")
     * @ORM\JoinColumn(nullable=false)
     */
    private $user;

    /**
     * @ORM\ManyToOne(targetEntity=Stock::class, inversedBy="transactions")
     * @ORM\JoinColumn(nullable=false)
     * @Groups({"transaction:read"})
     */
    private $stock;

    /**
     * @ORM\Column(type="string", length=10)
     * @Groups({"transaction:read"})
     */
    private $type;

    /**
     * @ORM\Column(type="integer")
     * @Groups({"transaction:read"})
     */
    private $quantity;

    /**
     * @ORM\Column(type="decimal", precision=14, scale=4)
     * @Groups({"transaction:read"})
     */
    private $price;

    /**
     * @ORM\Column(type="decimal", precision=16, scale=2)
     * @Groups({"transaction:read"})
     */
    private $totalAmount;

    /**
     * @ORM\Column(type="decimal", precision=14, scale=4, nullable=true)
     * @Groups({"transaction:read"})
     */
    private $fee;

    /**
     * @ORM\Column(type="datetime")
     * @Groups({"transaction:read"})
     */
    private $transactionDate;

    /**
     * @ORM\Column(type="text", nullable=true)
     * @Groups({"transaction:read"})
     */
    private $notes;

    /**
     * @ORM\Column(type="boolean")
     * @Groups({"transaction:read"})
     */
    private $isProcessed = false;

    /**
     * @ORM\ManyToOne(targetEntity=Portfolio::class)
     * @ORM\JoinColumn(nullable=false)
     */
    private $portfolio;

    public function __construct()
    {
        $this->transactionDate = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getStock(): ?Stock
    {
        return $this->stock;
    }

    public function setStock(?Stock $stock): self
    {
        $this->stock = $stock;
        $this->calculateTotalAmount();

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        if (!in_array($type, [self::TYPE_BUY, self::TYPE_SELL])) {
            throw new \InvalidArgumentException('Invalid transaction type');
        }

        $this->type = $type;

        return $this;
    }

    public function getQuantity(): ?int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): self
    {
        $this->quantity = $quantity;
        $this->calculateTotalAmount();

        return $this;
    }

    public function getPrice(): ?string
    {
        return $this->price;
    }

    public function setPrice(string $price): self
    {
        $this->price = $price;
        $this->calculateTotalAmount();

        return $this;
    }

    public function getTotalAmount(): ?string
    {
        return $this->totalAmount;
    }

    public function setTotalAmount(string $totalAmount): self
    {
        $this->totalAmount = $totalAmount;

        return $this;
    }

    public function getFee(): ?string
    {
        return $this->fee;
    }

    public function setFee(?string $fee): self
    {
        $this->fee = $fee;
        $this->calculateTotalAmount();

        return $this;
    }

    public function getTransactionDate(): ?\DateTimeInterface
    {
        return $this->transactionDate;
    }

    public function setTransactionDate(\DateTimeInterface $transactionDate): self
    {
        $this->transactionDate = $transactionDate;

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

    public function isIsProcessed(): ?bool
    {
        return $this->isProcessed;
    }

    public function setIsProcessed(bool $isProcessed): self
    {
        $this->isProcessed = $isProcessed;

        return $this;
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

    /**
     * Calculate the total transaction amount including fees
     */
    private function calculateTotalAmount(): void
    {
        if ($this->price && $this->quantity) {
            $baseAmount = (float) $this->price * $this->quantity;
            $feeAmount = $this->fee ? (float) $this->fee : 0;
            
            // Add fee for buy, subtract for sell
            if ($this->type === self::TYPE_BUY) {
                $totalAmount = $baseAmount + $feeAmount;
            } else {
                $totalAmount = $baseAmount - $feeAmount;
            }
            
            $this->totalAmount = (string) $totalAmount;
        }
    }
} 