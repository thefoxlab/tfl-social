<?php

declare(strict_types=1);

namespace TheFoxLab\TflSocial\Models;

use CodeIgniter\Model;
use TheFoxLab\TflSocial\Entities\Connection;

class ConnectionModel extends Model
{
    protected $table = 'social_connection';

    protected $primaryKey = 'social_connection_id';

    protected $returnType = Connection::class;

    protected $allowedFields = [
        'social_account_id',
        'parent_connection_id',
        'provider',
        'external_id',
        'external_name',
        'access_token',
        'refresh_token',
        'token_expires_at',
        'permissions',
        'status',
        'connected_at',
        'last_synced_at',
        'metadata',
    ];

    protected $useTimestamps = true;

    protected $useSoftDeletes = true;

    protected $dateFormat = 'datetime';

    protected $createdField = 'created_time';

    protected $updatedField = 'updated_time';

    protected $deletedField = 'deleted_time';
}
