<?php

namespace App\Repository\Modules\Discord;

use App\Entity\Modules\Discord\DiscordMessage;
use App\Entity\Modules\Discord\DiscordWebhook;
use App\Repository\Modules\CleanupInterface;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method DiscordWebhook|null find($id, $lockMode = null, $lockVersion = null)
 * @method DiscordWebhook|null findOneBy(array $criteria, array $orderBy = null)
 * @method DiscordWebhook[]    findAll()
 * @method DiscordWebhook[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DiscordWebhookRepository extends ServiceEntityRepository implements CleanupInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DiscordWebhook::class);
    }

    /**
     * Will return one discord webhook for provided hook name, if no such hook exists - null is returned
     *
     * @param string $webhookName
     * @return DiscordWebhook|null
     */
    public function getOneByWebhookName(string $webhookName): ?DiscordWebhook
    {
        $discordWebHook = $this->findOneBy([
            DiscordWebhook::FIELD_NAME_WEBHOOK_NAME => $webhookName
        ]);

        return $discordWebHook;
    }

    /**
     * Will return one webhook or null if no webhook for given id was found
     *
     * @param string $id
     * @return DiscordWebhook|null
     */
    public function getOneById(string $id): ?DiscordWebhook
    {
        return $this->find($id);
    }

    /**
     * Will save entity if it's a new one, or update already existing
     *
     * @param DiscordWebhook $discordWebhook
     * @return DiscordWebhook
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function save(DiscordWebhook $discordWebhook): DiscordWebhook
    {
        $this->_em->persist($discordWebhook);
        $this->_em->flush();

        return $discordWebhook;
    }

    /**
     * Will hard delete the entity
     * @param DiscordWebhook $entity
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function hardDelete(DiscordWebhook $entity): void
    {
        $this->_em->remove($entity);
        $this->_em->flush();;
    }

    /**
     * Will return placeholder webhook used to keep the messages despite the fact that parent webhooks are removed
     */
    public function getPlaceholderWebhook(): ?DiscordWebhook
    {
        return $this->findOneBy([
           DiscordWebhook::FIELD_NAME_WEBHOOK_NAME => DiscordWebhook::PLACEHOLDER_WEBHOOK_NAME
        ]);
    }

    /**
     * Will return webhook url if such was found OR null if no match was found
     *
     * @param string $webhookUrl
     *
     * @return DiscordWebhook|null
     */
    public function findOneByWebhookUrl(string $webhookUrl): ?DiscordWebhook
    {
        return $this->findOneBy([
            "webhookUrl" => $webhookUrl,
        ]);
    }

    /**
     * {@inheritDoc}
     *
     * @return DiscordWebhook[]
     */
    public function getEntriesToRemove(DateTime $olderThan): array
    {
        $qb = $this->_em->createQueryBuilder();
        $qb->select("dw")
           ->from(DiscordWebhook::class, "dw")
           ->join("dw.discordMessages", "dm", Join::WITH, "dm.discordWebhook = dw.id")
           ->where("dw.created < :olderThan")
           ->andWhere("dm.status = :status")
           ->setParameter("status", DiscordMessage::STATUS_SENT)
           ->setParameter("olderThan", $olderThan);

        return $qb->getQuery()->execute();
    }

}
