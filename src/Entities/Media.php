<?php

declare(strict_types=1);

namespace TheFoxLab\TflSocial\Entities;

use CodeIgniter\Entity\Entity;

class Media extends Entity
{
    protected $attributes = [
        'social_media_id' => null,
        'social_post_id' => null,
        'type' => null,
        'url' => null,
        'thumbnail_url' => null,
        'alt_text' => null,
        'sort_order' => null,
        'metadata' => null,
        'created_time' => null,
        'updated_time' => null,
        'deleted_time' => null,
    ];

    protected $casts = [
        'social_media_id' => '?integer',
        'social_post_id' => '?integer',
        'sort_order' => 'integer',
    ];
}
