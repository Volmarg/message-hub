<?php

namespace App\Entity\Modules\Mailing;

use App\Repository\Modules\Mailing\MailOpenStateRepository;
use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=MailOpenStateRepository::class)
 */
class MailOpenState
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="datetime")
     */
    private $created;

    /**
     * @ORM\Column(type="boolean")
     */
    private $open = false;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $openingToken;

    /**
     * @ORM\OneToOne(targetEntity=Mail::class, inversedBy="openState", cascade={"persist", "remove"})
     * @ORM\JoinColumn(nullable=false, unique=true)
     */
    private $email;

    public function __construct(string $token){
        $this->created      = new DateTime();
        $this->openingToken = $token;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCreated(): ?\DateTimeInterface
    {
        return $this->created;
    }

    public function setCreated(\DateTimeInterface $created): self
    {
        $this->created = $created;

        return $this;
    }

    public function isOpen(): ?bool
    {
        return $this->open;
    }

    public function setOpen(bool $open): self
    {
        $this->open = $open;

        return $this;
    }

    public function getOpeningToken(): ?string
    {
        return $this->openingToken;
    }

    public function setOpeningToken(string $openingToken): self
    {
        $this->openingToken = $openingToken;

        return $this;
    }

    public function getEmail(): ?Mail
    {
        return $this->email;
    }

    public function setEmail(Mail $email): self
    {
        $this->email = $email;

        return $this;
    }
}
