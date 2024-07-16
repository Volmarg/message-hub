<?php

namespace App\Repository\Modules;

use App\Command\Crons\Cleanup\CleanupCronCommand;
use DateTime;

/**
 * Enforces repository to implement method for returning remove-able entries for {@see CleanupCronCommand}
 */
interface CleanupInterface
{
    /**
     * Will return entries which should get removed by cleanup
     *
     * @param DateTime $olderThan
     *
     * @return array
     */
    public function getEntriesToRemove(DateTime $olderThan): array;
}