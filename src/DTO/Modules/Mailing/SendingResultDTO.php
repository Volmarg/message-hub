<?php

namespace App\DTO\Modules\Mailing;

use App\Entity\Modules\Mailing\MailAccount;

class SendingResultDTO
{
    public function __construct(
        private ?MailAccount $mailAccount,
        private bool         $success
    ){}

    /**
     * @return MailAccount|null
     */
    public function getMailAccount(): ?MailAccount
    {
        return $this->mailAccount;
    }

    /**
     * @return bool
     */
    public function isSuccess(): bool
    {
        return $this->success;
    }

}