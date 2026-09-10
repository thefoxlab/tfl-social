<?php

declare(strict_types=1);

namespace TheFoxLab\TflSocial\Models;

use CodeIgniter\Model;
use TheFoxLab\TflSocial\Entities\Account;

class AccountModel extends Model
{
    protected $table = 'social_account';

    protected $primaryKey = 'social_account_id';

    protected $returnType = Account::class;

    protected $allowedFields = [
        'name',
        'status',
        'metadata',
        'hashtag',
        'public_hashtag',
        'public_hashtag_id',
        'public_last_synced_at',
    ];

    protected $useTimestamps = true;

    protected $useSoftDeletes = true;

    protected $dateFormat = 'datetime';

    protected $createdField = 'created_time';

    protected $updatedField = 'updated_time';

    protected $deletedField = 'deleted_time';
}
