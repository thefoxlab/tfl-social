<?php

declare(strict_types=1);

namespace TheFoxLab\TflSocial\Entities;

use CodeIgniter\Entity\Entity;

class Post extends Entity
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
}
