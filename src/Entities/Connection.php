<?php

declare(strict_types=1);

namespace TheFoxLab\TflSocial\Entities;

use CodeIgniter\Entity\Entity;

class Connection extends Entity
{
    protected $attributes = [
        'social_connection_id' => null,
        'social_account_id' => null,
        'parent_connection_id' => null,
        'provider' => null,
        'external_id' => null,
        'external_name' => null,
        'access_token' => null,
        'refresh_token' => null,
        'token_expires_at' => null,
        'permissions' => null,
        'status' => null,
        'connected_at' => null,
        'last_synced_at' => null,
        'metadata' => null,
        'created_time' => null,
        'updated_time' => null,
        'deleted_time' => null,
    ];

    protected $casts = [
        'social_connection_id' => '?integer',
        'social_account_id' => '?integer',
        'parent_connection_id' => '?integer',
    ];
}
