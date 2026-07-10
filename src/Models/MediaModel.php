<?php

declare(strict_types=1);

namespace TheFoxLab\TflSocial\Models;

use CodeIgniter\Model;
use TheFoxLab\TflSocial\Entities\Media;

class MediaModel extends Model
{
    protected $table = 'social_media';

    protected $primaryKey = 'social_media_id';

    protected $returnType = Media::class;

    protected $allowedFields = [
        'social_post_id',
        'type',
        'url',
        'thumbnail_url',
        'alt_text',
        'sort_order',
        'metadata',
    ];

    protected $useTimestamps = true;

    protected $useSoftDeletes = true;

    protected $dateFormat = 'datetime';

    protected $createdField = 'created_time';

    protected $updatedField = 'updated_time';

    protected $deletedField = 'deleted_time';
}
