<?php

namespace App\Entity\Modules\Mailing;

use App\Repository\Modules\Mailing\MailAccountRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Mapping\ClassMetadata;

/**
 * @ORM\Entity(repositoryClass=MailAccountRepository::class)
 */
class MailAccount
{
    const DEFAULT_CONNECTION_NAME = "default";
    const FIELD_NAME              = "name";
    const FIELD_CLIENT            = "client";
    const FIELD_LOGIN             = "login";
    const FIELD_PASSWORD          = "password";
    const FIELD_HOST              = "host";

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=100)
     */
    private $client;

    /**
     * @ORM\Column(type="string", length=100)
     */
    private $login;

    /**
     * @ORM\Column(type="string", length=100)
     */
    private $password;

    /**
     * @ORM\Column(type="string", length=100, unique=true)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     */
    private string $host;

    /**
     * @var bool $active
     *
     * @ORM\Column(type="boolean")
     */
    private bool $active = true;

    /**
     * @ORM\OneToMany(targetEntity=Mail::class, mappedBy="account")
     */
    private $mails;

    public function __construct()
    {
        $this->mails = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getClient(): ?string
    {
        return $this->client;
    }

    public function setClient(string $client): self
    {
        $this->client = $client;

        return $this;
    }

    public function getLogin(): ?string
    {
        return $this->login;
    }

    public function setLogin(string $login): self
    {
        $this->login = $login;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

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

    /**
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->active;
    }

    /**
     * @param bool $active
     */
    public function setActive(bool $active): void
    {
        $this->active = $active;
    }

    /**
     * @return string
     */
    public function getHost(): string
    {
        return $this->host;
    }

    /**
     * @param string $host
     */
    public function setHost(string $host): void
    {
        $this->host = $host;
    }

    /**
     * @return string
     */
    public function buildFromEmail(): string
    {
        return "{$this->getLogin()}@{$this->getHost()}";
    }

    /**
     * @param ClassMetadata $metadata
     */
    public static function loadValidatorMetadata(ClassMetadata $metadata)
    {
        $metadata->addPropertyConstraint(self::FIELD_CLIENT, new NotBlank());
        $metadata->addPropertyConstraint(self::FIELD_NAME, new NotBlank());
        $metadata->addPropertyConstraint(self::FIELD_PASSWORD, new NotBlank());
        $metadata->addPropertyConstraint(self::FIELD_LOGIN, new NotBlank());
        $metadata->addPropertyConstraint(self::FIELD_HOST, new NotBlank());
    }

    /**
     * @return Collection|Mail[]
     */
    public function getMails(): Collection
    {
        return $this->mails;
    }

    public function addMail(Mail $mail): self
    {
        if (!$this->mails->contains($mail)) {
            $this->mails[] = $mail;
            $mail->setAccount($this);
        }

        return $this;
    }

    public function removeMail(Mail $mail): self
    {
        if ($this->mails->removeElement($mail)) {
            // set the owning side to null (unless already changed)
            if ($mail->getAccount() === $this) {
                $mail->setAccount(null);
            }
        }

        return $this;
    }
}
