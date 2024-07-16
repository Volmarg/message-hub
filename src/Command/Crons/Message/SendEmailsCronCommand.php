<?php

namespace App\Command\Crons\Message;

use App\Controller\Application;
use App\Controller\Core\Controllers;
use App\Controller\Modules\Mailing\Type\NotificationMailController;
use App\Controller\Modules\Mailing\Type\PlainMailController;
use App\Entity\Modules\Mailing\Mail;
use App\Services\Internal\Message\MailingService;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\LockableTrait;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use TypeError;

/**
 * Class SendEmailsCronCommand
 * @package App\Command\Assets
 */
class SendEmailsCronCommand extends Command
{
    use LockableTrait;

    const COMMAND_LOGGER_PREFIX = "[SendEmailsCronCommand] ";

    protected static $defaultName = 'mh:cron:send-emails';

    /**
     * @var Application $app
     */
    private $app;

    /**
     * @var Controllers
     */
    private Controllers $controllers;

    /**
     * @var SymfonyStyle $io
     */
    private $io = null;

    /**
     * @var NotificationMailController $notificationMailController
     */
    private NotificationMailController $notificationMailController;

    /**
     * @var PlainMailController $plainMailController
     */
    private PlainMailController $plainMailController;

    /**
     * @param Application                $app
     * @param Controllers                $controllers
     * @param NotificationMailController $notificationMailController
     * @param PlainMailController        $plainMailController
     * @param MailingService             $mailingService
     * @param EntityManagerInterface     $entityManager
     * @param string|null                $name
     */
    public function __construct(
        Application                    $app,
        Controllers                    $controllers,
        NotificationMailController     $notificationMailController,
        PlainMailController            $plainMailController,
        private MailingService         $mailingService,
        private EntityManagerInterface $entityManager,
        string $name = null
    ) {
        parent::__construct($name);
        $this->app                        = $app;
        $this->controllers                = $controllers;
        $this->plainMailController        = $plainMailController;
        $this->notificationMailController = $notificationMailController;
    }

    protected function configure()
    {
        $this
            ->setDescription("This command will attempt to send all processable emails");
    }

    protected function initialize(InputInterface $input, OutputInterface $output) {
        parent::initialize($input, $output);
        $this->io = new SymfonyStyle($input, $output);
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int
     * @throws Exception*@throws TransportExceptionInterface
     * @throws TransportExceptionInterface
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->app->getLoggerService()->getLogger()->info(self::COMMAND_LOGGER_PREFIX . "Started processing emails to send");
        if (!$this->lock(self::$defaultName)) {
            $this->io->info("Command is already running");
            return self::SUCCESS;
        }

        try{

            $allEmailsToProcess = $this->controllers->getMailingController()->getAllProcessableEmails();
            if( empty($allEmailsToProcess) ){
                $this->app->getLoggerService()->getLogger()->info(self::COMMAND_LOGGER_PREFIX . "There are no emails to process. Stopping here.");

                $this->release();
                return self::SUCCESS;
            }

            $countOfEmailsToProcess = count($allEmailsToProcess);
            $this->app->getLoggerService()->getLogger()->info(self::COMMAND_LOGGER_PREFIX . "Found {$countOfEmailsToProcess} emails to process.");

            foreach($allEmailsToProcess as $email){

                try{
                    $this->mailingService->sendEmailWithRandomAccount($email, true);
                    $this->controllers->getMailingController()->updateStatus($email, Mail::STATUS_SENT);
                }catch(Exception|TypeError $e){
                    $this->controllers->getMailingController()->updateStatus($email, Mail::STATUS_ERROR);
                    $this->app->getLoggerService()->logThrowable($e);
                    // no throwing, going further with all other entities
                }
            }

        }catch(Exception|TypeError $e){
            $this->app->getLoggerService()->logThrowable($e);

            $error = "[Message]: {$e->getMessage()}. [Trace] {$e->getTraceAsString()}";
            $email->setSendingError($error);

            $this->entityManager->persist($email);
            $this->entityManager->flush();

            $this->release();
            return self::FAILURE;
        }

        $this->app->getLoggerService()->getLogger()->info(self::COMMAND_LOGGER_PREFIX . "Finished processing emails to send");

        $this->release();
        return self::SUCCESS;
    }

}