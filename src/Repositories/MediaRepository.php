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

    /**
     * Finds media records due for refresh for a specific connection.
     *
     * @return list<Entity>
     */
    public function findDueForRefresh(int|string $connectionId, int $limit = 50): array
    {
        $postTable = $this->model->db->prefixTable('social_post');
        $mediaTable = $this->model->db->prefixTable('social_media');
        $now = date('Y-m-d H:i:s');

        $builder = $this->model->builder();
        $builder->select("{$mediaTable}.*")
            ->join($postTable, "{$postTable}.social_post_id = {$mediaTable}.social_post_id")
            ->where("{$postTable}.social_connection_id", (int) $connectionId)
            ->where("{$postTable}.status", 'active')
            ->where("{$mediaTable}.media_refresh_at IS NOT NULL")
            ->where("{$mediaTable}.media_refresh_at <=", $now)
            ->where("{$mediaTable}.deleted_time", null)
            ->orderBy("{$mediaTable}.media_refresh_at", 'ASC')
            ->limit($limit);

        $results = $builder->get()->getCustomResultObject(\TheFoxLab\TflSocial\Entities\Media::class);

        return $this->ensureEntityList($results);
    }
}
