<?php

namespace App\Controller\Modules\Mailing\Type;

use App\Controller\Modules\Mailing\MailAccountController;
use App\Entity\Modules\Mailing\Mail;
use App\Entity\Modules\Mailing\MailAccount;
use App\Services\Internal\Message\DsnService;
use Symfony\Component\EventDispatcher\Debug\TraceableEventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Stopwatch\Stopwatch;

/**
 * Handles sending plain E-mail:
 * - {@see Mail::isPlainEmail()}
 * - {@see MailerInterface}
 * - {@link https://symfony.com/doc/5.3/mailer.html#creating-sending-messages}
 */
class PlainMailController extends MailTypeController
{

    /**
     * @var EventDispatcherInterface $eventDispatcher
     */
    private EventDispatcherInterface $eventDispatcher;

    /**
     * @var MailAccountController $mailAccountController
     */
    private MailAccountController $mailAccountController;

    /**
     * @param EventDispatcherInterface $eventDispatcher
     * @param MailAccountController    $mailAccountController
     * @param DsnService               $dsnService
     */
    public function __construct(
        EventDispatcherInterface      $eventDispatcher,
        MailAccountController         $mailAccountController,
        private readonly DsnService   $dsnService
    )
    {
        $this->mailAccountController = $mailAccountController;
        $this->eventDispatcher       = $eventDispatcher;
    }

    /**
     * Will return {@see Mailer} client used for sending E-Mails
     *
     * @param string $dsnString
     * @return Mailer
     */
    public function getMailerClientForDsn(string $dsnString): Mailer
    {
        $stopWatch   = new Stopwatch(true);
        $dispatcher  = new TraceableEventDispatcher($this->eventDispatcher, $stopWatch);
        $transport   = Transport::fromDsn($dsnString, $dispatcher);
        $mailer      = new Mailer($transport);

        return $mailer;
    }

    /**
     * @param MailAccount $mailAccount
     *
     * @return Mailer
     */
    public function getMailerClientForAccount(MailAccount $mailAccount): Mailer
    {
        $dsn    = $this->dsnService->buildConnectionString($mailAccount);
        $mailer = $this->getMailerClientForDsn($dsn);

        return $mailer;
    }
}