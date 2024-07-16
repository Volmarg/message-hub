<?php

namespace App\Services\Internal\Cleanup\Member;

use App\Repository\Modules\CleanupInterface;
use App\Repository\Modules\Discord\DiscordWebhookRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class DiscordWebhookCleanupService extends BaseCleanupService
{
    public function __construct(
        /** @var CleanupInterface $mailRepository */
        private DiscordWebhookRepository $webhookRepository,
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
        foreach ($this->webhookRepository->getEntriesToRemove($this->getOlderThan()) as $message) {
            $this->entityManager->remove($message);
        }
    }
}