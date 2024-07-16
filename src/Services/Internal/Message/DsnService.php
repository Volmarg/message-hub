<?php

namespace App\Services\Internal\Message;

use App\Entity\Modules\Mailing\MailAccount;
use LogicException;

/**
 * Handles building / providing / manipulating the `dsn` string used for E-Mails sending
 * Eventually if needed can be expanded with paid services:
 * - {@link https://postmarkapp.com/pricing}
 * - {@link https://mailpace.com/#pricing}
 */
class DsnService
{
    public const SUPPORTED_CLIENTS = [
      self::CLIENT_GMAIL,
      self::CLIENT_ZOHOMAIL
    ];

    private const CLIENT_GMAIL    = "gmail";
    private const CLIENT_ZOHOMAIL = "zohomail";

    /**
     * Will build the mailer (MAILER_DSN) connection string used internally by symfony
     *
     * @param MailAccount $mailAccount
     * @return string
     */
    public function buildConnectionString(MailAccount $mailAccount): string
    {
        switch ($mailAccount->getClient()) {
            case self::CLIENT_GMAIL:
                return $this->buildForGmail($mailAccount);

            case self::CLIENT_ZOHOMAIL:
                return $this->buildForZohoMail($mailAccount);

            default:
                throw new LogicException("Cannot build dsn connection string for client {$mailAccount->getClient()} - it's not supported");
        }
    }

    /**
     * @param MailAccount $mailAccount
     *
     * @return string
     */
    private function buildForGmail(MailAccount $mailAccount): string
    {
        $connectionString = "gmail+smtp://{$mailAccount->getLogin()}:{$mailAccount->getPassword()}@default";

        return $connectionString;
    }

    /**
     * @param MailAccount $mailAccount
     *
     * @return string
     */
    private function buildForZohoMail(MailAccount $mailAccount): string
    {
        $dsnConnectionString = "smtp://{$mailAccount->getLogin()}:{$mailAccount->getPassword()}@smtp.zoho.eu:465?encryption=ssl";

        return $dsnConnectionString;
    }

}