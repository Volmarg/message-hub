<?php

namespace App\Services\Internal\Api;

use App\DTO\API\BaseApiResponseDto;
use App\Services\Internal\Validator\JsonValidatorService;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Contains logic for dealing with repetitive api call responses under different scenarios
 */
class ResponseHandlerService
{

    public function __construct(
        private readonly JsonValidatorService $jsonValidatorService,
        private readonly TranslatorInterface  $translator
    ){}

    /**
     * Handles json validation.
     * If valid   : response has success == true,
     * If invalid : response has success == false, and can be directly sent as response,
     *
     * @param string $json
     *
     * @return BaseApiResponseDto
     */
    public function validateJson(string $json): BaseApiResponseDto
    {
        $response = new BaseApiResponseDto();
        $response->prefillBaseFieldsForSuccessResponse();

        if (!$this->jsonValidatorService->isValidSyntax($json)) {
            $message = $this->translator->trans("api.external.general.messages.invalidJsonSyntax");
            $response->prefillBaseFieldsForBadRequestResponse();
            $response->setMessage($message);

            return $response;
        }

        return $response;
    }
}