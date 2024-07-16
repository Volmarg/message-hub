<?php

namespace App\Entity\Modules\Discord;

use App\Entity\EntityInterface;
use App\Entity\SoftDeletableInterface;
use App\Repository\Modules\Discord\DiscordWebhookRepository;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\UniqueConstraint;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Mapping\ClassMetadata;

/**
 * @ORM\Table(name="discord_webhook", uniqueConstraints={
 *  @UniqueConstraint(name="unique_name", columns={"webhook_name"})
 * })
 * @ORM\Entity(repositoryClass=DiscordWebhookRepository::class)
 */
class DiscordWebhook implements EntityInterface, SoftDeletableInterface
{
    const PLACEHOLDER_WEBHOOK_NAME = "placeholder";

    const FIELD_NAME_WEBHOOK_NAME = "webhookName";
    const FIELD_NAME_WEBHOOK_URL  = "webhookUrl";
    const FIELD_NAME_USERNAME     = "username";

    private const GENERIC_USER_NAME = "notifier";

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $username = self::GENERIC_USER_NAME;

    /**
     * @ORM\Column(type="text")
     */
    private $webhookUrl;

    /**
     * @ORM\Column(type="text")
     */
    private $description = "";

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $webhookName;

    /**
     * @ORM\OneToMany(targetEntity=DiscordMessage::class, mappedBy="discordWebhook")
     */
    private $discordMessages;

    /**
     * @ORM\Column(type="boolean")
     */
    private bool $deleted = false;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $created;

    public function __construct()
    {
        $this->created         = new DateTime();
        $this->discordMessages = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): self
    {
        $this->username = $username;

        return $this;
    }

    public function getWebhookUrl(): ?string
    {
        return $this->webhookUrl;
    }

    public function setWebhookUrl(string $webhookUrl): self
    {
        $this->webhookUrl = $webhookUrl;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * Null is allowed due to some issue when description is = "", some symfony mapping transforms this value into NULL
     * and if it's null then setter won't work, thus allowing null and setting to empty string
     *
     * @param string|null $description
     *
     * @return $this
     */
    public function setDescription(?string $description): self
    {
        $this->description = $description ?? "";

        return $this;
    }

    public function getWebhookName(): ?string
    {
        return $this->webhookName;
    }

    public function setWebhookName(string $webhookName): self
    {
        $this->webhookName = $webhookName;

        return $this;
    }

    /**
     * @return Collection|DiscordMessage[]
     */
    public function getDiscordMessages(): Collection
    {
        return $this->discordMessages;
    }

    /**
     * @return void
     */
    public function setUniqueWebhookName(): void
    {
        $this->webhookName = uniqid();
    }

    public function addDiscordMessage(DiscordMessage $discordMessage): self
    {
        if (!$this->discordMessages->contains($discordMessage)) {
            $this->discordMessages[] = $discordMessage;
            $discordMessage->setDiscordWebhook($this);
        }

        return $this;
    }

    public function removeDiscordMessage(DiscordMessage $discordMessage): self
    {
        if ($this->discordMessages->removeElement($discordMessage)) {
            // set the owning side to null (unless already changed)
            if ($discordMessage->getDiscordWebhook() === $this) {
                $discordMessage->setDiscordWebhook(null);
            }
        }

        return $this;
    }

    /**
     * @param ClassMetadata $metadata
     */
    public static function loadValidatorMetadata(ClassMetadata $metadata): void
    {
        $metadata->addPropertyConstraint(self::FIELD_NAME_WEBHOOK_NAME, new NotBlank());
        $metadata->addPropertyConstraint(self::FIELD_NAME_WEBHOOK_URL, new NotBlank());
        $metadata->addPropertyConstraint(self::FIELD_NAME_USERNAME, new NotBlank());
    }

    /**
     * @param bool $deleted
     */
    public function setDeleted(bool $deleted): void
    {
        $this->deleted = $deleted;
    }

    /**
     * @return bool
     */
    public function isDeleted(): bool
    {
        return $this->deleted;
    }

    /**
     * @return DateTime
     */
    public function getCreated(): DateTime
    {
        return $this->created;
    }

    /**
     * @param DateTime $created
     */
    public function setCreated(DateTime $created): void
    {
        $this->created = $created;
    }

}
