<?php

namespace App\Services\Internal\Validator;

use Psr\Log\LoggerInterface;

/**
 * Handles json (strings) validation
 */
class JsonValidatorService
{

    public function __construct(
        private LoggerInterface $logger
    ){}

    /**
     * Will validate the json last error, and if there is any it will log that error alongside with json itself,
     * Returns:
     * - `true` if there are no errors,
     * - `false` if there was some error
     *
     * @param string $json
     *
     * @return bool
     */
    public function isValidSyntax(string $json): bool
    {
        json_decode($json, true);
        if (JSON_ERROR_NONE !== json_last_error()) {
            $this->logger->warning("Provided json has invalid syntax", [
                "json_error" => json_last_error_msg(),
                "json"       => $json,
            ]);

            return false;
        }

        return true;
    }

}