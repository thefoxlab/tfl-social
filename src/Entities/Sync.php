<?php

declare(strict_types=1);

namespace TheFoxLab\TflSocial\Entities;

use CodeIgniter\Entity\Entity;
use JsonSerializable;
use TheFoxLab\TflSocial\Contracts\Arrayable;

class Sync extends Entity implements Arrayable, JsonSerializable
{
    protected $attributes = [
        'social_sync_id' => null,
        'social_account_id' => null,
        'social_connection_id' => null,
        'provider' => null,
        'status' => null,
        'started_at' => null,
        'finished_at' => null,
        'message' => null,
        'raw_json' => null,
        'created_time' => null,
        'updated_time' => null,
    ];

    protected $casts = [
        'social_sync_id' => '?integer',
        'social_account_id' => '?integer',
        'social_connection_id' => '?integer',
    ];

    /**
     * @return array<string, mixed>|list<mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
