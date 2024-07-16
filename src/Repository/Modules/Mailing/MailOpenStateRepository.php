<?php

namespace App\Repository\Modules\Mailing;

use App\Entity\Modules\Mailing\Mail;
use App\Entity\Modules\Mailing\MailOpenState;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method MailOpenState|null find($id, $lockMode = null, $lockVersion = null)
 * @method MailOpenState|null findOneBy(array $criteria, array $orderBy = null)
 * @method MailOpenState[]    findAll()
 * @method MailOpenState[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MailOpenStateRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MailOpenState::class);
    }

    /**
     * Check if {@see Mail} of given id has been opened.
     *
     * Returns:
     * - <bool> `true` if E-Mail of given id exists and has been opened,
     * - <bool> `false` if E-Mail of given id exists and has NOT been opened
     * - null if E-Mail of given id does not exist
     *
     * @param int $emailId
     *
     * @return bool|null
     * @throws NonUniqueResultException
     */
    public function isOpened(int $emailId): ?bool
    {
        $queryBuilder = $this->_em->createQueryBuilder();
        $queryBuilder->select("eo")
            ->from(MailOpenState::class,"eo")
            ->join(Mail::class, "e", Join::WITH, "e.id MEMBER OF eo.email")
            ->where("eo.id = :emailId")
            ->setParameter("emailId", $emailId);

        /** @var ?MailOpenState $emailOpenState */
        $emailOpenState = $queryBuilder->getQuery()->getOneOrNullResult();

        return $emailOpenState?->isOpen();
    }

    /**
     * Will search for entry with given token and will set the "open" state to true,
     * indicating that E-Mail has been opened
     *
     * @param string $token
     */
    public function setOpenedState(string $token): void
    {
        $queryBuilder = $this->_em->createQueryBuilder();
        $queryBuilder->update(MailOpenState::class, "eo")
            ->set("eo.open", true)
            ->where("eo.open = 0")
            ->andWhere("eo.openingToken = :token")
            ->setParameter("token", $token);

        $queryBuilder->getQuery()->execute();
    }

}
