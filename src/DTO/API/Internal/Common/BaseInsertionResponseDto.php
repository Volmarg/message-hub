<?php

namespace App\DTO\API\Internal\Common;

use App\DTO\API\BaseApiResponseDto;
use App\Traits\IdAwareTrait;

/**
 * Handles the data insertion
 */
abstract class BaseInsertionResponseDto extends BaseApiResponseDto
{
    use IdAwareTrait;

    /**
     * @return array
     */
    public function toArray(): array
    {
        $array                = parent::toArray();
        $array[self::$KEY_ID] = $this->getId();

        return $array;
    }
}