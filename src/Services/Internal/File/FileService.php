<?php

namespace App\Services\Internal\File;

use Psr\Log\LoggerInterface;

/**
 * Logic for files handling
 */
class FileService
{
    public function __construct(
        private LoggerInterface $logger
    ){}

    /**
     * Handles file removal
     *
     * @param string|null $filePath
     *
     * @return bool
     */
    public function remove(?string $filePath = null): bool
    {
        if (empty($filePath)) {
            $this->logger->warning("Cannot remove file, path is empty");
            return false;
        }

        if (!file_exists($filePath)) {
            $this->logger->warning("Cannot remove file - it does not exist under path: {$filePath}");
            return false;
        }

        $isRemoved = unlink($filePath);
        if (!$isRemoved) {
            $this->logger->warning("Cannot remove file, unlink threw FALSE for file path: {$filePath}");
            return false;
        }

        return true;
    }

}