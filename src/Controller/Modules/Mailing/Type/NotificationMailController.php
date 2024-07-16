<?php

namespace App\Controller\Modules\Mailing\Type;

use App\Controller\Core\ConfigLoaders;
use App\Controller\Modules\Mailing\MailAccountController;
use App\Entity\Modules\Mailing\Mail;
use App\Entity\Modules\Mailing\MailAccount;
use App\Services\Internal\Message\DsnService;
use Symfony\Component\EventDispatcher\Debug\TraceableEventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Notifier\Channel\EmailChannel;
use Symfony\Component\Notifier\Notification\Notification;
use Symfony\Component\Notifier\Notifier;
use Symfony\Component\Stopwatch\Stopwatch;

/**
 * Handles logic related to the {@see Notification} and thus {@see Mail::TYPE_NOTIFICATION}
 */
class NotificationMailController extends MailTypeController
{
    public const MAIL_CHANNEL_NAME = "email";

    /**
     * @var ConfigLoaders $configLoaders
     */
    private ConfigLoaders $configLoaders;

    /**
     * @var MailAccountController $mailAccountController
     */
    private MailAccountController $mailAccountController;

    /**
     * @var EventDispatcherInterface $eventDispatcher
     */
    private EventDispatcherInterface $eventDispatcher;

    /**
     * @param ConfigLoaders            $configLoaders
     * @param MailAccountController    $mailAccountController
     * @param EventDispatcherInterface $eventDispatcher
     * @param DsnService               $dsnService
     */
    public function __construct(
        ConfigLoaders                 $configLoaders,
        MailAccountController         $mailAccountController,
        EventDispatcherInterface      $eventDispatcher,
        private readonly DsnService   $dsnService
    )
    {
        $this->mailAccountController = $mailAccountController;
        $this->eventDispatcher       = $eventDispatcher;
        $this->configLoaders         = $configLoaders;
    }

    /**
     * Will return notifier instance for sending mail messages, uses MailAccount configuration
     *
     * @param MailAccount $mailAccount
     * @return Notifier
     */
    public function getNotifierForSendingMailNotifications(MailAccount $mailAccount): Notifier
    {
        $dsnConnectionString = $this->dsnService->buildConnectionString($mailAccount);
        $notifier            = $this->buildNotifierForDsnString($dsnConnectionString, $mailAccount);
        return $notifier;
    }

    /**
     * Will build the mailer (MAILER_DSN) connection string used internally by symfony
     *
     * @param string      $dsnString
     * @param MailAccount $mailAccount
     *
     * @return Notifier
     */
    public function buildNotifierForDsnString(string $dsnString, MailAccount $mailAccount): Notifier
    {
        $stopWatch   = new Stopwatch(true);
        $dispatcher  = new TraceableEventDispatcher($this->eventDispatcher, $stopWatch);
        $transport   = Transport::fromDsn($dsnString, $dispatcher);
        $mailChannel = new EmailChannel($transport, null, $mailAccount->buildFromEmail());
        $notifier    = new Notifier([self::MAIL_CHANNEL_NAME => $mailChannel]);

        return $notifier;
    }

}