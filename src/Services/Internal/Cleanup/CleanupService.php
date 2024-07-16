<?php

namespace App\Services\Internal\Cleanup;

use App\Services\Internal\Cleanup\Member\BaseCleanupService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Handles variety of data cleaning
 */
class CleanupService
{
    public function __construct(
        private LoggerInterface        $logger,
        private EntityManagerInterface $entityManager,
        private                        $members = [],
    ) {}

    public function cleanUp(): void {
        /** @var BaseCleanupService $cleanupServiceMember */
        foreach ($this->members as $cleanupServiceMember) {
            $this->logger->info("Now cleaning entries for service: " . $cleanupServiceMember::class);
            $cleanupServiceMember->exectue();
        }

        // doing it once for all service as it's will be better performance wise
        $this->entityManager->flush();
    }
}