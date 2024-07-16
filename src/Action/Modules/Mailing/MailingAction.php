<?php

namespace App\Action\Modules\Mailing;

use App\Controller\Application;
use App\Controller\Core\Controllers;
use App\Controller\Core\Env;
use App\Controller\Modules\Mailing\Type\NotificationMailController;
use App\DTO\API\BaseApiResponseDto;
use App\DTO\API\Internal\Email\GetSupportedMailClientsResponseDto;
use App\DTO\API\Internal\GetAllEmailsAccountsResponseDto;
use App\DTO\API\Internal\GetAllEmailsResponseDto;
use App\DTO\Modules\Mailing\MailAccountDTO;
use App\DTO\Modules\Mailing\MailDTO;
use App\DTO\Modules\Mailing\SendTestMailDTO;
use App\Entity\Modules\Mailing\Mail;
use App\Entity\Modules\Mailing\MailAccount;
use App\Services\Internal\FormService;
use App\Services\Internal\Message\DsnService;
use App\Services\Internal\Message\MailingService;
use App\Services\Internal\ValidationService;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Notifier\NotifierInterface;
use Symfony\Component\Routing\Annotation\Route;

#[Route("/modules/mailing", name: "modules_mailing_")]
class MailingAction extends AbstractController
{

    /**
     * @var Application $application
     */
    private Application $application;

    /**
     * @var NotifierInterface $notifier
     */
    private NotifierInterface $notifier;

    /**
     * @var FormService $formService
     */
    private FormService $formService;

    /**
     * @var Controllers $controllers
     */
    private Controllers $controllers;

    /**
     * @var ValidationService $validationService
     */
    private ValidationService $validationService;

    /**
     * @var NotificationMailController $notificationMailController
     */
    private NotificationMailController $notificationMailController;

    public function __construct(
        Application                $application,
        NotifierInterface          $notifier,
        FormService                $formService,
        Controllers                $controllers,
        ValidationService          $validationService,
        NotificationMailController $notificationMailController,
        private MailingService     $mailingService
    )
    {
        $this->notificationMailController = $notificationMailController;
        $this->validationService           = $validationService;
        $this->controllers                 = $controllers;
        $this->application                 = $application;
        $this->formService                 = $formService;
        $this->notifier                    = $notifier;
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    #[Route("/send-test-mail", name: "send_test_mail", methods: ["POST"])]
    public function sendTestMail(Request $request): JsonResponse
    {
        try{
            if (Env::isDemo()) {
                $baseResponseDto = BaseApiResponseDto::buildBadRequestErrorResponse();
                $baseResponseDto->setMessage("Demo version: sending has been disabled");
                return $baseResponseDto->toJsonResponse();
            }

            $testMailForm = $this->application->getForms()->getSendTestMailForm();
            $message      = $this->application->trans('pages.mailing.sendTestMail.messages.success');

            $baseResponseDto = new BaseApiResponseDto();
            $baseResponseDto->prefillBaseFieldsForSuccessResponse();

            $testMailForm = $this->formService->handlePostFormForAxiosCall($testMailForm, $request);
            if( $testMailForm->isSubmitted() && $testMailForm->isValid() ){
                /**
                 * @var SendTestMailDTO $sendTestMailDto
                 */
                $sendTestMailDto = $testMailForm->getData();

                $mail = new Mail();
                $mail->setBody($sendTestMailDto->getMessageBody());
                $mail->setSubject($sendTestMailDto->getMessageTitle());
                $mail->setToEmails([$sendTestMailDto->getReceiver()]);

                if (empty($sendTestMailDto->getAccount())) {
                    $message = $this->application->trans('pages.mailing.sendTestMail.messages.fails.noEmailAccountHasBeenSelected');
                    $baseResponseDto = BaseApiResponseDto::buildBadRequestErrorResponse();
                    $baseResponseDto->setMessage($message);

                    return $baseResponseDto->toJsonResponse();
                }else{
                    $mailAccount = $this->controllers->getMailAccountController()->getOneById($sendTestMailDto->getAccount());
                    $notifier    = $this->notificationMailController->getNotifierForSendingMailNotifications($mailAccount);
                }

                if( empty($notifier) ){
                    $message = $this->application->trans('pages.mailing.sendTestMail.messages.fail');
                    $baseResponseDto->prefillBaseFieldsForBadRequestResponse();
                    $this->application->getLoggerService()->getLogger()->critical("No email account has been found for given account id", [
                        "accountId" => $sendTestMailDto->getAccount(),
                    ]);
                }

                $this->controllers->getMailingController()->sendSingleEmailViaNotifier($mail, $notifier, false);
            }elseif( $testMailForm->isSubmitted() && !$testMailForm->isValid() ){
                $message = $this->application->trans('pages.mailing.sendTestMail.messages.fail');
                $baseResponseDto->prefillBaseFieldsForBadRequestResponse();
            }

            $baseResponseDto->setMessage($message);
            return $baseResponseDto->toJsonResponse();
        }catch(NotFoundHttpException $nfe){
            $this->application->getLoggerService()->logThrowable($nfe);

            $message = $this->application->trans('pages.mailing.sendTestMail.messages.defaultAccountIsNotDefined');

            $baseResponseDto = BaseApiResponseDto::buildBadRequestErrorResponse();
            $baseResponseDto->setMessage($message);

            return $baseResponseDto->toJsonResponse();
        }catch(Exception $e){
            $this->application->getLoggerService()->logThrowable($e);

            $message = $this->application->trans('pages.mailing.sendTestMail.messages.fail');

            $baseResponseDto = BaseApiResponseDto::buildInternalServerErrorResponse();
            $baseResponseDto->setMessage($message);

            return $baseResponseDto->toJsonResponse();
        }
    }

    /**
     * Will return all emails
     * @return JsonResponse
     */
    #[Route("/get-all-emails", name: "get_all_emails", methods: ["GET"])]
    public function getAllEmails(): JsonResponse
    {
        try{
            $mails = $this->controllers->getMailingController()->getAllEmails();

            $arrayOfDtosJsons = [];
            foreach($mails as $mail){
                $mailDto = new MailDTO($mail->getId());
                $mailDto->setBody($mail->getBody());
                $mailDto->setSubject($mail->getSubject());
                $mailDto->setCreated($mail->getCreated()->format("Y-m-d H:i:s"));
                $mailDto->setToEmails($mail->getToEmails());
                $mailDto->setStatus($mail->getStatus());

                $arrayOfDtosJsons[] = $mailDto->toJson();
            }

            $responseDto = new GetAllEmailsResponseDto();
            $responseDto->prefillBaseFieldsForSuccessResponse();
            $responseDto->setEmailsJsons($arrayOfDtosJsons);

            return $responseDto->toJsonResponse();
        }catch(Exception $e){
            $this->application->getLoggerService()->logThrowable($e);

            $message = $this->application->trans('pages.mailing.history.messages.errors.couldNotGetAllSentEmails');

            $baseResponseDto = BaseApiResponseDto::buildInternalServerErrorResponse();
            $baseResponseDto->setMessage($message);

            return $baseResponseDto->toJsonResponse();
        }
    }

    /**
     * Will return all mails accounts
`     * @return JsonResponse
     */
    #[Route("/get-all-mails-accounts", name: "get_all_mails_accounts", methods: ["GET"])]
    public function getAllMailsAccount(): JsonResponse
    {
        try{
            $mailsAccounts = $this->controllers->getMailAccountController()->getAllMailAccounts();

            $arrayOfDtosJsons = [];
            foreach($mailsAccounts as $mailAccount){
                $mailAccountDto = new MailAccountDTO();
                $mailAccountDto->setId($mailAccount->getId());
                $mailAccountDto->setName($mailAccount->getName());
                $mailAccountDto->setClient($mailAccount->getClient());
                $mailAccountDto->setHost($mailAccount->getHost());
                $mailAccountDto->setLogin($mailAccount->getLogin());
                $mailAccountDto->setPassword($mailAccount->getPassword()); // todo: encrypt that somewhere in future

                $arrayOfDtosJsons[] = $mailAccountDto->toJson();
            }

            $responseDto = new GetAllEmailsAccountsResponseDto();
            $responseDto->prefillBaseFieldsForSuccessResponse();
            $responseDto->setEmailsAccountsJsons($arrayOfDtosJsons);

            return $responseDto->toJsonResponse();
        }catch(Exception $e){
            $this->application->getLoggerService()->logThrowable($e);

            $message = $this->application->trans('pages.mailing.getAllMailsAccount.messages.errors.couldNotGetAllEmailsAccounts');

            $baseResponseDto = BaseApiResponseDto::buildInternalServerErrorResponse();
            $baseResponseDto->setMessage($message);

            return $baseResponseDto->toJsonResponse();
        }
    }

    /**
     * Will return all supported mail clients
     * @return JsonResponse
     */
    #[Route("/get-supported-mail-clients", name: "get_supported_mail_clients", methods: ["GET"])]
    public function getSupportedMailClients(): JsonResponse
    {
        try{
            $responseDto = new GetSupportedMailClientsResponseDto();
            $responseDto->prefillBaseFieldsForSuccessResponse();
            $responseDto->setClients(DsnService::SUPPORTED_CLIENTS);

            return $responseDto->toJsonResponse();
        }catch(Exception $e){
            $this->application->getLoggerService()->logThrowable($e);

            $message = $this->application->trans('pages.mailing.getMailClients.message.errors.couldNotGetSupportedClients');

            $baseResponseDto = BaseApiResponseDto::buildInternalServerErrorResponse();
            $baseResponseDto->setMessage($message);

            return $baseResponseDto->toJsonResponse();
        }
    }

    /**
     * Handles the frontend (axios) request to add the mail account
     *
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    #[Route("/add-mail-account", name: "add_mail_account", methods: ["POST"])]
    public function addMailAccount(Request $request): JsonResponse
    {
        try{
            $successMessage    = $this->application->trans('pages.mailing.addMailAccount.messages.success');
            $badRequestMessage = $this->application->trans('pages.mailing.addMailAccount.messages.invalidDataHasBeenProvided');

            $baseResponseDto = new BaseApiResponseDto();
            $baseResponseDto->prefillBaseFieldsForSuccessResponse();
            $baseResponseDto->setMessage($successMessage);

            $mailAccountForm = $this->application->getForms()->getMailAccount();
            $this->formService->handlePostFormForAxiosCall($mailAccountForm, $request);

            if( $mailAccountForm->isSubmitted() ){
                $mailAccount = $mailAccountForm->getData();
                $violations  = $this->validationService->validateAndReturnArrayOfInvalidFieldsWithMessages($mailAccount);

                if( !empty($violations) ){
                    $message = $this->application->trans('pages.mailing.addMailAccount.messages.invalidDataHasBeenProvided');
                    $baseResponseDto->prefillBaseFieldsForBadRequestResponse();
                    $baseResponseDto->setInvalidFields($violations);
                    $baseResponseDto->setMessage($message);
                    return $baseResponseDto->toJsonResponse();
                }

                if( $mailAccountForm->isValid() ){
                    $this->controllers->getMailAccountController()->saveMailAccount($mailAccount);
                }else{
                    $baseResponseDto->prefillBaseFieldsForBadRequestResponse();
                    $baseResponseDto->setMessage($badRequestMessage);
                }

            }else{
                $baseResponseDto->prefillBaseFieldsForBadRequestResponse();
                $baseResponseDto->setMessage($badRequestMessage);
            }
        }catch(Exception $e){
            $this->application->getLoggerService()->logThrowable($e);

            $message = $this->application->trans('pages.mailing.addMailAccount.messages.fail');

            $baseResponseDto = BaseApiResponseDto::buildInternalServerErrorResponse();
            $baseResponseDto->setMessage($message);

            return $baseResponseDto->toJsonResponse();
        }

        return $baseResponseDto->toJsonResponse();
    }

    /**
     * Handles the removal of single mail account
     *
     * @param string mailAccountId
     * @return JsonResponse
     */
    #[Route("/remove-mail-account/{mailAccountId}", name: "remove_mail_account", methods: [ "GET" ])]
    public function removeMailAccount(string $mailAccountId): JsonResponse
    {
        try{
            $mailAccount = $this->controllers->getMailAccountController()->getOneById($mailAccountId);
            if( empty($mailAccount) ){
                $message = $this->application->trans('pages.mailing.removeMailAccount.messages.fail.noMailAccountWasFound');
                return BaseApiResponseDto::buildBadRequestErrorResponse($message)->toJsonResponse();
            }

            $this->controllers->getMailAccountController()->hardDelete($mailAccount);

            $successMessage = $this->application->trans('pages.mailing.removeMailAccount.messages.success');
            $baseResponseDto = new BaseApiResponseDto();
            $baseResponseDto->prefillBaseFieldsForSuccessResponse();
            $baseResponseDto->setMessage($successMessage);
        }catch(Exception $e){
            $this->application->getLoggerService()->logThrowable($e);

            $message = $this->application->trans('pages.mailing.removeMailAccount.messages.fail.failedToRemove');

            $baseResponseDto = BaseApiResponseDto::buildInternalServerErrorResponse();
            $baseResponseDto->setMessage($message);

            return $baseResponseDto->toJsonResponse();
        }

        return $baseResponseDto->toJsonResponse();
    }

    /**
     * Handles the frontend (axios) request to update the mail account
     *
     * @param Request $request
     * @param string $mailAccountId
     * @return JsonResponse
     */
    #[Route("/update-mail-account/{mailAccountId}", name: "update_mail_account", methods: ["POST"])]
    public function updateMailAccount(Request $request, string $mailAccountId): JsonResponse
    {
        try{
            $successMessage    = $this->application->trans('pages.mailing.updateMailAccount.messages.success');
            $badRequestMessage = $this->application->trans('pages.mailing.addMailAccount.messages.invalidDataHasBeenProvided');

            $baseResponseDto = new BaseApiResponseDto();
            $baseResponseDto->prefillBaseFieldsForSuccessResponse();
            $baseResponseDto->setMessage($successMessage);

            $mailAccountForm = $this->application->getForms()->getMailAccount();
            $mailAccountForm = $this->formService->handlePostFormForAxiosCall($mailAccountForm, $request);

            /** @var $updatedMailAccountData MailAccount */
            if( $mailAccountForm->isSubmitted() ){

                $mailAccount = $this->controllers->getMailAccountController()->getOneById($mailAccountId);
                if( empty($mailAccount) ){
                    $message = $this->application->trans('pages.mailing.updateMailAccount.messages.noMailAccountHasBeenFound');
                    return BaseApiResponseDto::buildBadRequestErrorResponse($message)->toJsonResponse();
                }

                $updatedMailAccountData = $mailAccountForm->getData();
                $violations             = $this->validationService->validateAndReturnArrayOfInvalidFieldsWithMessages($updatedMailAccountData);

                if( !empty($violations) ){
                    $baseResponseDto->prefillBaseFieldsForBadRequestResponse();
                    $baseResponseDto->setInvalidFields($violations);
                    $baseResponseDto->setMessage($badRequestMessage);
                    return $baseResponseDto->toJsonResponse();
                }

                if( $mailAccountForm->isValid() ){
                    $mailAccount->setClient($updatedMailAccountData->getClient());
                    $mailAccount->setName($updatedMailAccountData->getName());
                    $mailAccount->setLogin($updatedMailAccountData->getLogin());
                    $mailAccount->setPassword($updatedMailAccountData->getPassword());

                    $this->controllers->getMailAccountController()->save($mailAccount);
                }else{
                    $baseResponseDto->prefillBaseFieldsForBadRequestResponse();
                    $baseResponseDto->setMessage($badRequestMessage);
                }

            }else{
                $message = $this->application->trans('pages.mailing.updateMailAccount.messages.badRequest');
                return BaseApiResponseDto::buildBadRequestErrorResponse($message)->toJsonResponse();
            }

        }catch(Exception $e){
            $this->application->getLoggerService()->logThrowable($e);

            $message = $this->application->trans('pages.mailing.updateMailAccount.messages.fail');

            $baseResponseDto = BaseApiResponseDto::buildInternalServerErrorResponse();
            $baseResponseDto->setMessage($message);

            return $baseResponseDto->toJsonResponse();
        }

        return $baseResponseDto->toJsonResponse();
    }

    /**
     * @param Mail $email
     *
     * @return JsonResponse
     * @throws TransportExceptionInterface
     */
    #[Route("/re-send-email/{id}", name: "resend_email", methods: [Request::METHOD_GET])]
    public function reSendEmail(Mail $email): JsonResponse
    {
        try {
            if (Env::isDemo()) {
                $baseResponseDto = BaseApiResponseDto::buildBadRequestErrorResponse();
                $baseResponseDto->setMessage("Demo version: sending has been disabled");
                return $baseResponseDto->toJsonResponse();
            }

            $baseResponseDto = new BaseApiResponseDto();
            if (!$email->isFailed()) {
                $message = $this->application->trans('pages.mailing.history.messages.errors.cannotReSendThisEmail');

                $baseResponseDto->prefillBaseFieldsForBadRequestResponse();
                $baseResponseDto->setMessage($message);

                return $baseResponseDto->toJsonResponse();
            }

            $this->mailingService->sendEmailWithRandomAccount($email);

            $message = $this->application->trans('pages.mailing.history.messages.success.messageHasBeenSent');
            return $baseResponseDto->prefillBaseFieldsForSuccessResponse($message)->toJsonResponse();
        } catch (Exception $e) {
            $this->application->getLoggerService()->logThrowable($e);

            $message = $this->application->trans('pages.mailing.history.messages.errors.errorWhileReSendingEmail');

            $error = "[Message]: {$e->getMessage()}. [Trace] {$e->getTraceAsString()}";
            $email->setReSendingError($error);

            $this->getDoctrine()->getManager()->persist($email);
            $this->getDoctrine()->getManager()->flush();

            $baseResponseDto = BaseApiResponseDto::buildInternalServerErrorResponse();
            $baseResponseDto->setMessage($message);

            return $baseResponseDto->toJsonResponse();
        }
    }

    /**
     * @param Mail   $email
     * @param string $emailAddress
     *
     * @return JsonResponse
     * @throws TransportExceptionInterface
     */
    #[Route("/send-to/{id}/{emailAddress}", name: "send_to", methods: [Request::METHOD_GET])]
    public function sendTo(Mail $email, string $emailAddress): JsonResponse
    {
        try {
            if (Env::isDemo()) {
                $baseResponseDto = BaseApiResponseDto::buildBadRequestErrorResponse();
                $baseResponseDto->setMessage("Demo version: sending has been disabled");
                return $baseResponseDto->toJsonResponse();
            }

            $baseResponseDto = new BaseApiResponseDto();
            if (!filter_var($emailAddress, FILTER_VALIDATE_EMAIL)) {
                $message = $this->application->trans('pages.mailing.history.messages.errors.syntheticallyInvalidEmail');

                $baseResponseDto = new BaseApiResponseDto();
                $baseResponseDto->prefillBaseFieldsForBadRequestResponse();
                $baseResponseDto->setMessage($message);

                return $baseResponseDto->toJsonResponse();
            }

            $clonedEmail = clone $email;
            $clonedEmail->setToEmails([$emailAddress]);

            $this->mailingService->sendEmailWithRandomAccount($clonedEmail, true, false);

            $message = $this->application->trans('pages.mailing.history.messages.success.messageHasBeenSent');
            return $baseResponseDto->prefillBaseFieldsForSuccessResponse($message)->toJsonResponse();
        } catch (Exception $e) {
            $this->application->getLoggerService()->logThrowable($e);

            $message = $this->application->trans('pages.mailing.history.messages.errors.errorWhileReSendingEmail');

            $error = "[Message]: {$e->getMessage()}. [Trace] {$e->getTraceAsString()}";
            $email->setSendToError($error);

            $this->getDoctrine()->getManager()->persist($email);
            $this->getDoctrine()->getManager()->flush();

            $baseResponseDto = BaseApiResponseDto::buildInternalServerErrorResponse();
            $baseResponseDto->setMessage($message);

            return $baseResponseDto->toJsonResponse();
        }
    }

}