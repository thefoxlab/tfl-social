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
        'provider',
        'external_id',
        'name',
        'status',
        'metadata',
    ];

    protected $useTimestamps = true;

    protected $useSoftDeletes = true;

    protected $dateFormat = 'datetime';

    protected $createdField = 'created_time';

    protected $updatedField = 'updated_time';

    protected $deletedField = 'deleted_time';
}
