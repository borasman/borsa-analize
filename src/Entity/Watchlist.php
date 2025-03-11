<?php

namespace App\Entity;

use App\Repository\WatchlistRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity(repositoryClass=WatchlistRepository::class)
 */
class Watchlist
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     * @Groups({"watchlist:read"})
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=100)
     * @Groups({"watchlist:read"})
     */
    private $name;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="watchlists")
     * @ORM\JoinColumn(nullable=false)
     */
    private $user;

    /**
     * @ORM\Column(type="datetime")
     * @Groups({"watchlist:read"})
     */
    private $createdAt;

    /**
     * @ORM\OneToMany(targetEntity=WatchlistItem::class, mappedBy="watchlist", orphanRemoval=true)
     * @Groups({"watchlist:read"})
     */
    private $items;

    /**
     * @ORM\Column(type="boolean")
     * @Groups({"watchlist:read"})
     */
    private $isDefault = false;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->items = new ArrayCollection();
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

    /**
     * @return Collection<int, WatchlistItem>
     */
    public function getItems(): Collection
    {
        return $this->items;
    }

    public function addItem(WatchlistItem $item): self
    {
        if (!$this->items->contains($item)) {
            $this->items[] = $item;
            $item->setWatchlist($this);
        }

        return $this;
    }

    public function removeItem(WatchlistItem $item): self
    {
        if ($this->items->removeElement($item)) {
            // set the owning side to null (unless already changed)
            if ($item->getWatchlist() === $this) {
                $item->setWatchlist(null);
            }
        }

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
} 