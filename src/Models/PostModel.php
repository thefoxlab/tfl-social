<?php

declare(strict_types=1);

namespace TheFoxLab\TflSocial\Models;

use CodeIgniter\Model;
use TheFoxLab\TflSocial\Entities\Post;

class PostModel extends Model
{
    protected $table = 'social_post';

    protected $primaryKey = 'social_post_id';

    protected $returnType = Post::class;

    protected $allowedFields = [
        'social_connection_id',
        'provider',
        'external_id',
        'type',
        'message',
        'caption',
        'permalink',
        'published_at',
        'metrics',
        'raw_json',
        'status',
    ];

    protected $useTimestamps = true;

    protected $useSoftDeletes = true;

    protected $dateFormat = 'datetime';

    protected $createdField = 'created_time';

    protected $updatedField = 'updated_time';

    protected $deletedField = 'deleted_time';
}
