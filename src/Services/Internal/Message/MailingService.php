<?php

namespace App\Services\Internal\Message;

use App\Controller\Core\ConfigLoaders;
use App\Controller\Modules\Mailing\MailAttachmentController;
use App\Controller\Modules\Mailing\MailController;
use App\Controller\Modules\Mailing\MailTrackerController;
use App\Controller\Modules\Mailing\Type\NotificationMailController;
use App\Controller\Modules\Mailing\Type\PlainMailController;
use App\DTO\Modules\Mailing\MailDTO;
use App\DTO\Modules\Mailing\SendingResultDTO;
use App\Entity\Modules\Mailing\Mail;
use App\Entity\Modules\Mailing\MailAttachment;
use App\Repository\Modules\Mailing\MailAccountRepository;
use App\Services\Internal\LoggerService;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Exception;
use LogicException;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mime\Email;
use TypeError;

/**
 * Provides logic for sending emails
 */
class MailingService
{
    public function __construct(
        private NotificationMailController        $notificationMailController,
        private MailController                    $mailController,
        private PlainMailController               $plainMailController,
        private LoggerService                     $loggerService,
        private readonly MailAccountRepository    $mailAccountRepository,
        private readonly DsnService               $dsnService,
        private readonly MailTrackerController    $mailTrackerController,
        private readonly MailAttachmentController $attachmentController,
        private readonly KernelInterface          $kernel,
        private readonly ConfigLoaders            $configLoaders
    ){}

    /**
     * Will send single email
     *
     * @param Mail $email
     * @param bool $retryOnFail
     * @param bool $createOrUpdate
     *
     * @throws TransportExceptionInterface
     */
    public function sendEmailWithRandomAccount(Mail $email, bool $retryOnFail = false, bool $createOrUpdate = true): void
    {
        $isFail       = false;
        $isSent       = false;
        $accountsUsed = [];

        while (!$isSent) {

            if(
                    !$retryOnFail
                &   $isFail
            ){
                $emailId = $email->getId() ?? "is not set";
                throw new Exception("Failed sending email, retry is disabled. Email id: {$emailId}");
            }

            try{
                $sendingResultDto = $this->handleSendingByEmailType($email, $accountsUsed, $createOrUpdate);
                if (empty($sendingResultDto->getMailAccount())) {
                    break;
                }

                if (!$sendingResultDto->isSuccess()) {
                    $accountsUsed[] = $sendingResultDto->getMailAccount();
                    $isFail         = !$sendingResultDto->isSuccess();
                }

                $isSent = $sendingResultDto->isSuccess();
            } catch (Exception $e) {
                $this->loggerService->logThrowable($e);
                $isFail = true;
            }

        }
    }

    /**
     * Will insert {@see Email} created from {@see MailDTO} alongside with the {@see MailAttachment}
     *
     * @param MailDTO $mailDto
     *
     * @return Mail|null
     * @throws Exception
     */
    public function insertEmailFromDto(MailDTO $mailDto): ?Mail
    {
        $mailAttachmentsDirectoryAbsolutePath = null;

        try {
            $mail = $this->mailController->buildMailEntityFromMailDto($mailDto);
            $mail = $this->mailTrackerController->handle($mail, $mailDto);
            $mail = $this->mailController->saveEntity($mail); // must be saved first to obtain the id

            if (!empty($mailDto->getAttachments())) {
                $mailAttachmentsDirectoryRelativePath = $this->configLoaders->getSystemDataConfigLoader()->getRelativeMailAttachmentsFolder() . DIRECTORY_SEPARATOR . $mail->getId();
                $mailAttachmentsDirectoryAbsolutePath = $this->kernel->getProjectDir() . $mailAttachmentsDirectoryRelativePath;

                $mail = $this->attachmentController->buildAttachmentsForMail($mail, $mailDto, $mailAttachmentsDirectoryAbsolutePath, $mailAttachmentsDirectoryRelativePath);
                $mail = $this->mailController->saveEntity($mail); // save attachments
            }

        } catch(Exception | TypeError $e) {
            // this mean that attachments were created before something crashed - got to remove them
            if(
                    !empty($mailAttachmentsDirectoryAbsolutePath)
                &&  file_exists($mailAttachmentsDirectoryAbsolutePath)
            ){
                rmdir($mailAttachmentsDirectoryAbsolutePath);
            }

            throw $e;
        }

        return $mail;
    }

    /**
     * Will handle the email by its type
     *
     * @param Mail  $email
     * @param array $skippedAccounts
     * @param bool  $createOrUpdate
     *
     * @return SendingResultDTO
     * @throws TransportExceptionInterface
     */
    private function handleSendingByEmailType(Mail $email, array $skippedAccounts, bool $createOrUpdate = true): SendingResultDTO
    {
        $this->validateEmail($email);

        switch($email->getType())
        {
            case Mail::TYPE_NOTIFICATION:
                {
                    $sendingResultDto = $this->handleSendingNotification($email, $skippedAccounts, $createOrUpdate);
                }
                break;

            case Mail::TYPE_PLAIN:
                {
                    $sendingResultDto = $this->handleSendingPlainMail($email, $skippedAccounts, $createOrUpdate);
                }
                break;

            default:
            {
                throw new LogicException("This Mail type is not supported: {$email->getType()}");
            }
        }

        return $sendingResultDto;
    }

    /**
     * Handles sending notification
     *
     * @param Mail  $email
     * @param array $skippedAccounts
     * @param bool  $createOrUpdate
     *
     * @return SendingResultDTO
     *
     */
    private function handleSendingNotification(Mail $email, array $skippedAccounts, bool $createOrUpdate = true): SendingResultDTO
    {
        $randomAccount = $this->mailAccountRepository->getRandomActive($skippedAccounts);
        if (empty($randomAccount)) {
            $this->loggerService->getLogger()->critical($this->getNoAccountMessage());
            return new SendingResultDTO($randomAccount, false);
        }

        try {
            $dsn      = $this->dsnService->buildConnectionString($randomAccount);
            $notifier = $this->notificationMailController->buildNotifierForDsnString($dsn, $randomAccount);

            $email->setAccount($randomAccount);

            $this->mailController->sendSingleEmailViaNotifier($email, $notifier, $createOrUpdate);
        } catch (Exception|TypeError $e) {
            $this->loggerService->logThrowable($e, [
                "mailAccount" => [
                    "id"      => $randomAccount->getId(),
                    "address" => $randomAccount->buildFromEmail(),
                ]
            ]);

            return new SendingResultDTO($randomAccount, false);
        }

        return new SendingResultDTO($randomAccount, true);
    }

    /**
     * Handles sending plain mail
     *
     * @param Mail  $email
     * @param array $skippedAccounts
     * @param bool  $storeInDb
     *
     * @return SendingResultDTO
     *
     * @throws TransportExceptionInterface
     * @throws Exception
     */
    private function handleSendingPlainMail(Mail $email, array $skippedAccounts, bool $storeInDb = true): SendingResultDTO
    {
        $randomAccount = $this->mailAccountRepository->getRandomActive($skippedAccounts);
        if (empty($randomAccount)) {
            $this->loggerService->getLogger()->critical($this->getNoAccountMessage());
            return new SendingResultDTO($randomAccount, false);
        }

        try {
            $mailer = $this->plainMailController->getMailerClientForAccount($randomAccount);
            $email->setAccount($randomAccount);

            $this->mailController->sendSingleEmailViaMailer($email, $mailer, $randomAccount, $storeInDb);
        } catch (Exception|TypeError $e) {
            $this->loggerService->logThrowable($e,[
                "mailAccount" => [
                    "id"      => $randomAccount->getId(),
                    "address" => $randomAccount->buildFromEmail(),
                ]
            ]);

            return new SendingResultDTO($randomAccount, false);
        }

        return new SendingResultDTO($randomAccount, true);
    }

    /**
     * Returns message "no mail accounts available"
     *
     * @return string
     */
    private function getNoAccountMessage(): string
    {
        return "Either there are no MailAccount/s available for sending this message or all have failed to send";
    }

    /**
     * Will validate E-Mail before attempting to send it
     *
     * @param Mail $email
     *
     * @return void
     * @throws Exception
     */
    private function validateEmail(Mail $email): void
    {
        if (!filter_var($email->getFromEmail(), FILTER_VALIDATE_EMAIL)) {
            throw new Exception("This is not synthetically valid `from` E-mail: {$email->getFromEmail()}");
        }

        foreach ($email->getToEmails() as $email) {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception("This is not synthetically valid `to` E-mail: {$email}");
            }
        }
    }
}