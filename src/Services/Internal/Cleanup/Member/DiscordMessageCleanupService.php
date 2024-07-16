<?php

namespace App\Services\Internal\Cleanup\Member;

use App\Repository\Modules\CleanupInterface;
use App\Repository\Modules\Discord\DiscordMessageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class DiscordMessageCleanupService extends BaseCleanupService
{
    public function __construct(
        /** @var CleanupInterface $mailRepository */
        private DiscordMessageRepository $messageRepository,
        private EntityManagerInterface   $entityManager,
        private LoggerInterface          $logger
    ){
        parent::__construct($this->logger);
    }

    /**
     * {@inheritDoc}
     */
    protected function cleanUp(): void
    {
        foreach ($this->messageRepository->getEntriesToRemove($this->getOlderThan()) as $message) {
            $this->entityManager->remove($message);
        }
    }
}