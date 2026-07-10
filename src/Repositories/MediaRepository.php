<?php

declare(strict_types=1);

namespace TheFoxLab\TflSocial\Repositories;

use TheFoxLab\TflSocial\Models\MediaModel;

final class MediaRepository extends AbstractRepository
{
    public function __construct(?MediaModel $model = null)
    {
        parent::__construct($model ?? new MediaModel(), 'social_media_id');
    }
}
