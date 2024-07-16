<?php

namespace App\Repository\Modules\Mailing;

use App\Entity\Modules\Mailing\MailAccount;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method MailAccount|null find($id, $lockMode = null, $lockVersion = null)
 * @method MailAccount|null findOneBy(array $criteria, array $orderBy = null)
 * @method MailAccount[]    findAll()
 * @method MailAccount[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MailAccountRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MailAccount::class);
    }

    /**
     * Will create new entity if such one does not exist or update the existing one
     *
     * @param MailAccount $mailAccount
     * @return MailAccount
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function saveMailAccount(MailAccount $mailAccount): MailAccount
    {
        $this->_em->persist($mailAccount);
        $this->_em->flush();

        return $mailAccount;
    }

    /**
     * Wil return the default mail account
     *
     * @return null|MailAccount
     */
    public function getDefaultMailAccount(): ?MailAccount
    {
        return $this->findOneBy([
            MailAccount::FIELD_NAME => MailAccount::DEFAULT_CONNECTION_NAME,
        ]);
    }

    /**
     * Will return all mail accounts
     *
     * @return MailAccount[]
     */
    public function getAllMailAccounts(): array
    {
        return $this->findAll();
    }

    /**
     * Will return one mail account or null if none for given id was found
     *
     * @param string $id
     * @return MailAccount|null
     */
    public function getOneById(string $id): ?MailAccount
    {
        return $this->find($id);
    }

    /**
     * Will hard delete the entity
     *
     * @param MailAccount $entity
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function hardDelete(MailAccount $entity): void
    {
        $this->_em->remove($entity);
        $this->_em->flush();;
    }

    /**
     * Will return all active accounts
     *
     * @param array $skippedAccounts
     *
     * @return array
     */
    public function getAllActive(array $skippedAccounts = []): array
    {
        $ids = array_map(
            fn(MailAccount $mailAccount) => $mailAccount->getId(),
            $skippedAccounts
        );

        $qb = $this->_em->createQueryBuilder();
        $qb->select("ma")
           ->from(MailAccount::class, "ma")
           ->where("ma.active = 1");

        if (!empty($ids)) {
            $qb->andWhere("ma.id NOT IN (:ids)")
               ->setParameter("ids", $ids)
            ;
        }

        $results = $qb->getQuery()->execute();

        return $results;
    }

    /**
     * Will return one random account, skips provided accounts,
     * If no matching account is found then null is returned
     *
     * @param array $skippedAccounts
     *
     * @return MailAccount|null
     */
    public function getRandomActive(array $skippedAccounts = []): ?MailAccount
    {
        $accounts = $this->getAllActive($skippedAccounts);
        if (empty($accounts)) {
            return null;
        }

        $randomAccount = $accounts[array_rand($accounts)];

        return $randomAccount;
    }

    /**
     * Will save entity if it's a new one, or update already existing
     *
     * @param MailAccount $mailAccount
     * @return MailAccount
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function save(MailAccount $mailAccount): MailAccount
    {
        $this->_em->persist($mailAccount);
        $this->_em->flush();

        return $mailAccount;
    }

}
