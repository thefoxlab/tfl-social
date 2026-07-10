<?php

declare(strict_types=1);

namespace TheFoxLab\TflSocial\Entities;

use CodeIgniter\Entity\Entity;

class Account extends Entity
{
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
}
