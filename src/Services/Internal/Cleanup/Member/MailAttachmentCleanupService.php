<?php

namespace App\Services\Internal\Cleanup\Member;

use App\Repository\Modules\CleanupInterface;
use App\Repository\Modules\Mailing\MailAttachmentRepository;
use App\Services\Internal\File\FileService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class MailAttachmentCleanupService extends BaseCleanupService
{
    public function __construct(
        /** @var CleanupInterface  $mailAttachmentRepository*/
        private MailAttachmentRepository $mailAttachmentRepository,
        private EntityManagerInterface   $entityManager,
        private FileService              $fileService,
        private LoggerInterface          $logger
    ){
        parent::__construct($this->logger);
    }

    /**
     * {@inheritDoc}
     */
    protected function cleanUp(): void
    {
        foreach ($this->mailAttachmentRepository->getEntriesToRemove($this->getOlderThan()) as $attachment) {

            $isRemoved = $this->fileService->remove($attachment->getPath());
            if (!$isRemoved) {
                $this->logger->warning("Could not mail attachment, skipping entity removal", [
                    "entityId" => $attachment->getId(),
                    "filePath" => $attachment->getPath()
                ]);
                continue;
            }

            $this->entityManager->remove($attachment);
        }
    }
}