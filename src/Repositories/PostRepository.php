<?php

declare(strict_types=1);

namespace TheFoxLab\TflSocial\Repositories;

use CodeIgniter\Entity\Entity;
use TheFoxLab\TflSocial\Models\PostModel;

final class PostRepository extends AbstractRepository
{
    public function __construct(?PostModel $model = null)
    {
        parent::__construct($model ?? new PostModel(), 'social_post_id');
    }

    public function findByConnectionExternalId(int|string $connectionId, string $externalId): ?Entity
    {
        return $this->findOne([
            'social_connection_id' => $connectionId,
            'external_id' => $externalId,
        ]);
    }
}
