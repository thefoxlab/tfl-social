<?php

declare(strict_types=1);

namespace TheFoxLab\TflSocial\Repositories;

use CodeIgniter\Entity\Entity;
use TheFoxLab\TflSocial\Models\MediaModel;

final class MediaRepository extends AbstractRepository
{
    public function __construct(?MediaModel $model = null)
    {
        parent::__construct($model ?? new MediaModel(), 'social_media_id');
    }

    /**
     * @return list<Entity>
     */
    public function findByPostId(int|string $postId): array
    {
        return $this->findAll([
            'social_post_id' => $postId,
        ]);
    }

    public function findByPostSortOrder(int|string $postId, int $sortOrder): ?Entity
    {
        return $this->findOne([
            'social_post_id' => $postId,
            'sort_order' => $sortOrder,
        ]);
    }
}
