<?php

namespace App\Entity;

use App\Repository\NotificationRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity(repositoryClass=NotificationRepository::class)
 * @ORM\Table(indexes={
 *     @ORM\Index(name="notification_created_at_idx", columns={"created_at"}),
 *     @ORM\Index(name="notification_read_at_idx", columns={"read_at"})
 * })
 */
class Notification
{
    const TYPE_PRICE_ALERT = 'price_alert';
    const TYPE_SYSTEM = 'system';
    const TYPE_NEWS = 'news';
    const TYPE_TRANSACTION = 'transaction';
    
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     * @Groups({"notification:read"})
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="notifications")
     * @ORM\JoinColumn(nullable=false)
     */
    private $user;

    /**
     * @ORM\Column(type="string", length=50)
     * @Groups({"notification:read"})
     */
    private $type;

    /**
     * @ORM\Column(type="string", length=255)
     * @Groups({"notification:read"})
     */
    private $title;

    /**
     * @ORM\Column(type="text", nullable=true)
     * @Groups({"notification:read"})
     */
    private $message;

    /**
     * @ORM\Column(type="json", nullable=true)
     * @Groups({"notification:read"})
     */
    private $data = [];

    /**
     * @ORM\Column(type="datetime")
     * @Groups({"notification:read"})
     */
    private $createdAt;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     * @Groups({"notification:read"})
     */
    private $readAt;

    /**
     * @ORM\Column(type="boolean")
     * @Groups({"notification:read"})
     */
    private $isRead = false;

    /**
     * @ORM\ManyToOne(targetEntity=Stock::class, nullable=true)
     * @Groups({"notification:read"})
     */
    private $stock;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
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

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $validTypes = [self::TYPE_PRICE_ALERT, self::TYPE_SYSTEM, self::TYPE_NEWS, self::TYPE_TRANSACTION];
        
        if (!in_array($type, $validTypes)) {
            throw new \InvalidArgumentException(sprintf(
                'Invalid notification type. Valid types are: %s',
                implode(', ', $validTypes)
            ));
        }
        
        $this->type = $type;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(?string $message): self
    {
        $this->message = $message;

        return $this;
    }

    public function getData(): ?array
    {
        return $this->data;
    }

    public function setData(?array $data): self
    {
        $this->data = $data;

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

    public function getReadAt(): ?\DateTimeInterface
    {
        return $this->readAt;
    }

    public function setReadAt(?\DateTimeInterface $readAt): self
    {
        $this->readAt = $readAt;
        
        if ($readAt !== null) {
            $this->isRead = true;
        }

        return $this;
    }

    public function isIsRead(): ?bool
    {
        return $this->isRead;
    }

    public function setIsRead(bool $isRead): self
    {
        $this->isRead = $isRead;
        
        if ($isRead && $this->readAt === null) {
            $this->readAt = new \DateTime();
        }

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

    /**
     * Mark notification as read
     */
    public function markAsRead(): self
    {
        $this->isRead = true;
        $this->readAt = new \DateTime();
        
        return $this;
    }
} 