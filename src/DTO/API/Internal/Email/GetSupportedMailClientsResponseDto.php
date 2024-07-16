<?php

namespace App\DTO\API\Internal\Email;

use App\DTO\API\BaseApiResponseDto;

class GetSupportedMailClientsResponseDto extends BaseApiResponseDto
{
    const KEY_CLIENTS = "clients";

    private array $clients = [];

    /**
     * @return array
     */
    public function getClients(): array
    {
        return $this->clients;
    }

    /**
     * @param array $clients
     */
    public function setClients(array $clients): void
    {
        $this->clients = $clients;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        $array                    = parent::toArray();
        $array[self::KEY_CLIENTS] = $this->getClients();

        return $array;
    }
}