<?php

namespace App\Services\Internal\Cleanup\Member;

use DateTime;
use Psr\Log\LoggerInterface;

abstract class BaseCleanupService
{
    private DateTime $olderThan;

    private int $countRemovedEntries = 0;

    public function __construct(
        private LoggerInterface $logger
    ){}

    /**
     * Performs cleaning up the data of given type, this method should contain any necessary checks and should also
     * call the {@see EntityManagerInterface::remove()} in order to mark the entry for removal,
     *
     * The flush itself should be done only once for all cleanup classes, and happens indeed in:
     * - {@see CleanupService::cleanUp()}
     */
    abstract protected function cleanUp();

    /**
     * Execute the cleaning logic
     */
    public function exectue(): void
    {
        $this->cleanUp();
        $this->afterCleanup();
    }

    /**
     * Contains logic called directly after {@see BaseCleanupService::exectue()}
     */
    public function afterCleanup(): void
    {
        $this->logger->info("Removed {$this->getCountRemovedEntries()} entries");
    }

    /**
     * @param int $daysOffset
     */
    public function setOlderThan(int $daysOffset): void
    {
        $this->olderThan = (new DateTime())->modify("-{$daysOffset} DAYS");
    }

    /**
     * @return DateTime
     */
    public function getOlderThan(): DateTime
    {
        return $this->olderThan;
    }

    /**
     * @return int
     */
    public function getCountRemovedEntries(): int
    {
        return $this->countRemovedEntries;
    }

    /**
     * @param int $countRemovedEntries
     */
    public function setCountRemovedEntries(int $countRemovedEntries): void
    {
        $this->countRemovedEntries = $countRemovedEntries;
    }

    public function incrementRemovedEntries(): void
    {
        $this->countRemovedEntries++;
    }
}