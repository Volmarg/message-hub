<?php

namespace App\Repository\Modules\Mailing;

use App\Entity\Modules\Mailing\Mail;
use App\Entity\Modules\Mailing\MailAttachment;
use App\Repository\Modules\CleanupInterface;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method MailAttachment|null find($id, $lockMode = null, $lockVersion = null)
 * @method MailAttachment|null findOneBy(array $criteria, array $orderBy = null)
 * @method MailAttachment[]    findAll()
 * @method MailAttachment[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MailAttachmentRepository extends ServiceEntityRepository implements CleanupInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MailAttachment::class);
    }

    /**
     * {@inheritDoc}
     * @return MailAttachment[]
     */
    public function getEntriesToRemove(DateTime $olderThan): array
    {
        $qb = $this->_em->createQueryBuilder();
        $qb->select("ma")
            ->from(MailAttachment::class, "ma")
            ->join("ma.email", "m", Join::WITH, "ma.email = m.id")
            ->where("ma.created < :olderThan")
            ->andWhere("m.status = :status")
            ->setParameter("status", Mail::STATUS_SENT)
            ->setParameter("olderThan", $olderThan);

        return $qb->getQuery()->execute();
    }

}
