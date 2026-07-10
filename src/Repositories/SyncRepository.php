<?php

declare(strict_types=1);

namespace TheFoxLab\TflSocial\Repositories;

use TheFoxLab\TflSocial\Models\SyncModel;

final class SyncRepository extends AbstractRepository
{
    public function __construct(?SyncModel $model = null)
    {
        parent::__construct($model ?? new SyncModel(), 'social_sync_id');
    }
}
