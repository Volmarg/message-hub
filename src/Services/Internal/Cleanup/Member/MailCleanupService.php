<?php

namespace App\Services\Internal\Cleanup\Member;

use App\Repository\Modules\CleanupInterface;
use App\Repository\Modules\Mailing\MailRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class MailCleanupService extends BaseCleanupService
{
    public function __construct(
        /** @var CleanupInterface $mailRepository */
        private MailRepository         $mailRepository,
        private EntityManagerInterface $entityManager,
        private LoggerInterface          $logger
    ){
        parent::__construct($this->logger);
    }

    /**
     * {@inheritDoc}
     */
    protected function cleanUp(): void
    {
        foreach ($this->mailRepository->getEntriesToRemove($this->getOlderThan()) as $message) {
            $this->entityManager->remove($message);
        }
    }

}