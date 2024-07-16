<?php

namespace App\Command\Crons\Cleanup;

use App\Services\Internal\Cleanup\CleanupService;
use App\Services\Internal\LoggerService;
use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\LockableTrait;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TypeError;

class CleanupCronCommand extends Command
{
    use LockableTrait;

    const COMMAND_LOGGER_PREFIX = "[" . self::class . "]";

    protected static $defaultName = 'mh:cron:cleanup';

    /**
     * @var SymfonyStyle $io
     */
    private $io = null;

    public function __construct(
        private CleanupService $cleanupService,
        private LoggerService  $logger,
        string                 $name = null
    ) {
        parent::__construct($name);
    }

    protected function configure()
    {
        $this
            ->setDescription("Handles removal of old data from DB")
        ;
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);
        $this->io = new SymfonyStyle($input, $output);
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->logger->getLogger()->info(self::COMMAND_LOGGER_PREFIX . " Started cleaning the entries in DB");
        if (!$this->lock(self::$defaultName)) {
            $this->io->info("Command is already running");
            return self::SUCCESS;
        }

        try {
            $this->cleanupService->cleanUp();
        } catch (Exception|TypeError $e) {
            $this->logger->logThrowable($e);

            $this->release();
            return self::FAILURE;
        }

        $this->logger->getLogger()->info(self::COMMAND_LOGGER_PREFIX . " Finished cleaning the entries in DB");

        $this->release();
        return self::SUCCESS;
    }

}