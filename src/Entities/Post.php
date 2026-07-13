<?php

declare(strict_types=1);

namespace TheFoxLab\TflSocial\Entities;

use CodeIgniter\Entity\Entity;
use JsonSerializable;
use TheFoxLab\TflSocial\Contracts\Arrayable;

class Post extends Entity implements Arrayable, JsonSerializable
{
    protected $attributes = [
        'social_post_id' => null,
        'social_connection_id' => null,
        'provider' => null,
        'external_id' => null,
        'type' => null,
        'message' => null,
        'caption' => null,
        'permalink' => null,
        'published_at' => null,
        'metrics' => null,
        'raw_json' => null,
        'status' => null,
        'created_time' => null,
        'updated_time' => null,
        'deleted_time' => null,
    ];

    protected $casts = [
        'social_post_id' => '?integer',
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
