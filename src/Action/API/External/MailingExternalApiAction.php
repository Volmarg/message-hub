<?php

namespace App\Action\API\External;

use App\Attributes\IsApiRoute;
use App\Controller\Application;
use App\Controller\Core\ConfigLoaders;
use App\Controller\Core\Controllers;
use App\Controller\Modules\Mailing\MailOpenStateController;
use App\DTO\API\BaseApiResponseDto;
use App\DTO\API\Internal\Email\GetEmailStatusResponseDto;
use App\DTO\API\Internal\Email\InsertEmailResponseDto;
use App\DTO\Modules\Mailing\MailDTO;
use App\Entity\Modules\Mailing\Mail;
use App\Entity\Modules\Mailing\MailOpenState;
use App\Services\Internal\Api\ResponseHandlerService;
use App\Services\Internal\Message\MailingService;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Routing\Annotation\Route;
use TypeError;

#[Route("/api/external", name: "api_external_")]
class MailingExternalApiAction extends AbstractController
{

    /**
     * @var Application $app
     */
    private Application $app;

    /**
     * @var Controllers $controllers
     */
    private Controllers $controllers;

    /**
     * @var ConfigLoaders $configLoaders
     */
    private ConfigLoaders $configLoaders;

    /**
     * @var EntityManagerInterface $entityManager
     */
    private EntityManagerInterface $entityManager;

    /**
     * @var KernelInterface $kernel
     */
    private KernelInterface $kernel;

    /**
     * @param Application             $app
     * @param Controllers             $controllers
     * @param ConfigLoaders           $configLoaders
     * @param EntityManagerInterface  $entityManager
     * @param KernelInterface         $kernel
     * @param MailOpenStateController $mailOpenStateController
     * @param MailingService          $mailingService
     * @param ResponseHandlerService  $responseHandlerService
     */
    public function __construct(
        Application                              $app,
        Controllers                              $controllers,
        ConfigLoaders                            $configLoaders,
        EntityManagerInterface                   $entityManager,
        KernelInterface                          $kernel,
        private readonly MailOpenStateController $mailOpenStateController,
        private readonly MailingService          $mailingService,
        private readonly ResponseHandlerService  $responseHandlerService,
    )
    {
        $this->app           = $app;
        $this->kernel        = $kernel;
        $this->entityManager = $entityManager;
        $this->configLoaders = $configLoaders;
        $this->controllers   = $controllers;
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    #[IsApiRoute]
    #[Route("/mailing/insert-mail", name: "mailing_insert_mail", methods: ["POST"])]
    public function insertMail(Request $request): JsonResponse
    {
        try{
            $this->entityManager->beginTransaction();
            $this->app->getLoggerService()->getLogger()->info("Inserting E-Mail");

            $responseDto = new InsertEmailResponseDto();
            $responseDto->prefillBaseFieldsForSuccessResponse();

            $json               = $request->getContent();
            $validationResponse = $this->responseHandlerService->validateJson($json);
            if (!$validationResponse->isSuccess()) {
                return $validationResponse->toJsonResponse();
            }

            $mailDto = MailDTO::fromJson($json);

            /**
             * The project is able to handle multiple `to` emails, but it will take way too much time to handle
             * the "state links" handling for all the emails etc. So this is only added to reduce to work needed for now.
             */
            if (!$mailDto->assertMaxToEmails()) {
                $message = MailDTO::MAX_TO_EMAILS . " `to` E-mail/s allowed, got: " . $mailDto->countToEmails() . " E-mail/s.";
                return $responseDto->prefillBaseFieldsForBadRequestResponse($message)->toJsonResponse();
            }

            $mail = $this->mailingService->insertEmailFromDto($mailDto);

            $message = $this->app->trans("api.external.general.messages.ok");
            $responseDto->setMessage($message);
            $responseDto->setId($mail->getId());

            $this->entityManager->commit();
            $this->app->getLoggerService()->getLogger()->info("Api call finished with success");

            return $responseDto->toJsonResponse();
        }catch(Exception| TypeError $e){
            $this->entityManager->rollback();

            $this->app->getLoggerService()->logThrowable($e, [
                "info" => "Issue occurred while handling external API method for inserting mail"
            ]);
            $message = $this->app->trans('api.external.general.messages.internalServerError');

            $responseDto = BaseApiResponseDto::buildInternalServerErrorResponse();
            $responseDto->setMessage($message);

            return $responseDto->toJsonResponse();
        }
    }

    /**
     * Does the same as {@see MailingExternalApiAction::insertMail()}, but will additionally send the E-Mail
     *
     * @param Request $request
     *
     * @return JsonResponse
     * @throws TransportExceptionInterface
     */
    #[IsApiRoute]
    #[Route("/mailing/direct-send", name: "mailing_direct_send", methods: ["POST"])]
    public function directSendMail(Request $request): JsonResponse
    {
        try {
            $this->entityManager->beginTransaction();

            $responseDto = (new BaseApiResponseDto())->prefillBaseFieldsForSuccessResponse();
            $json        = $request->getContent();

            $validationResponse = $this->responseHandlerService->validateJson($json);
            if (!$validationResponse->isSuccess()) {
                return $validationResponse->toJsonResponse();
            }

            $mailDto = MailDTO::fromJson($json);

            /**
             * The project is able to handle multiple `to` emails, but it will take way too much time to handle
             * the "state links" handling for all the emails etc. So this is only added to reduce to work needed for now.
             */
            if (!$mailDto->assertMaxToEmails()) {
                $message = MailDTO::MAX_TO_EMAILS . " `to` E-mail/s allowed, got: " . $mailDto->countToEmails() . " E-mail/s.";
                return $responseDto->prefillBaseFieldsForBadRequestResponse($message)->toJsonResponse();
            }

            $mail = $this->mailingService->insertEmailFromDto($mailDto);
            $this->mailingService->sendEmailWithRandomAccount($mail, true);

            $this->entityManager->commit();
        } catch (Exception|TypeError $e) {
            $this->entityManager->rollback();

            $this->app->getLoggerService()->logThrowable($e, [
                "info" => "Issue occurred while handling external API method for direct sending mail"
            ]);
            $message = $this->app->trans('api.external.general.messages.internalServerError');

            $responseDto = BaseApiResponseDto::buildInternalServerErrorResponse();
            $responseDto->setMessage($message);

            return $responseDto->toJsonResponse();
        }

        return $responseDto->toJsonResponse();
    }

    /**
     * Will return email status {@see Mail::ALL_STATUSES}
     *
     * @param int $id
     *
     * @return JsonResponse
     */
    #[IsApiRoute]
    #[Route("/mailing/get-mail-status/{id}", name: "mailing_get_mail_status", requirements: ["id" => "\d+"], methods: [Request::METHOD_GET])]
    public function getMailStatus(int $id): JsonResponse
    {
        try{
            $this->app->getLoggerService()->getLogger()->info("API method has been called: ", [
                __CLASS__ . "::" . __METHOD__,
            ]);

            $responseDto  = new GetEmailStatusResponseDto();
            $responseDto->prefillBaseFieldsForSuccessResponse();

            $email = $this->controllers->getMailingController()->findOne($id);
            if (empty($email) ){
                $message  = $this->app->trans("api.external.general.messages.notFound");
                $response = GetEmailStatusResponseDto::buildBadRequestErrorResponse($message);
                $response->setStatus(Mail::STATUS_ERROR);
                return $response->toJsonResponse();
            }

            $message = $this->app->trans("api.external.general.messages.ok");
            $responseDto->setMessage($message);
            $responseDto->setStatus($email->getStatus());

            $this->app->getLoggerService()->getLogger()->info("Api call finished with success");

            return $responseDto->toJsonResponse();
        }catch(Exception | TypeError $e){
            $this->app->getLoggerService()->logThrowable($e, [
                "info" => "Issue occurred while handling external API method for getting E-Mail status"
            ]);
            $message = $this->app->trans('api.external.general.messages.internalServerError');

            $responseDto = GetEmailStatusResponseDto::buildInternalServerErrorResponse();
            $responseDto->setMessage($message);

            return $responseDto->toJsonResponse();
        }
    }

    /**
     * Will set the {@see MailOpenState} to opened, if E-Mail for provided token exists
     * Keep in mind that this MUST be {@see Request::METHOD_GET}, as this will be used
     * as "hidden" image in E-Mail, so upon opening the E-Mail this url will get called
     *
     * @param string $token
     *
     * @return JsonResponse
     */
    #[Route("/mailing/vindicti-memoriare/{token}", name: "mailing_set_open_state", methods: [Request::METHOD_GET])]
    public function setMailOpenState(string $token): JsonResponse
    {
        try{
            $this->app->getLoggerService()->getLogger()->info("API method has been called: ", [
                __CLASS__ . "::" . __METHOD__,
            ]);

            $this->mailOpenStateController->setOpenedState($token);

            $response = (new BaseApiResponseDto())->prefillBaseFieldsForSuccessResponse();
            return $response->toJsonResponse();
        } catch (Exception|TypeError $e) {
            $this->app->getLoggerService()->logThrowable($e, [
                "info" => "Issue occurred while setting Mail open sate to: Opened"
            ]);
            $message = $this->app->trans('api.external.general.messages.internalServerError');

            $responseDto = (new BaseApiResponseDto())::buildInternalServerErrorResponse();
            $responseDto->setMessage($message);

            return $responseDto->toJsonResponse();
        }
    }

}