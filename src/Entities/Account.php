<?php

declare(strict_types=1);

namespace TheFoxLab\TflSocial\Entities;

use CodeIgniter\Entity\Entity;
use JsonSerializable;
use TheFoxLab\TflSocial\Contracts\Arrayable;

class Account extends Entity implements Arrayable, JsonSerializable
{
    public const STATUS_ACTIVE = '1';

    protected $attributes = [
        'social_account_id' => null,
        'name' => null,
        'status' => null,
        'metadata' => null,
        'created_time' => null,
        'updated_time' => null,
        'deleted_time' => null,
    ];

    protected $casts = [
        'social_account_id' => '?integer',
    ];

    /**
     * @return array<string, mixed>|list<mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
