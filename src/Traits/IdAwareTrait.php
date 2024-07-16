<?php

namespace App\Traits;

trait IdAwareTrait
{
    public static string $KEY_ID = "id";

    /**
     * @var int $id
     */
    private int $id;

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId(int $id): void
    {
        $this->id = $id;
    }

}