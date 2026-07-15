<?php

declare(strict_types=1);

namespace TheFoxLab\TflSocial\Services;

use CodeIgniter\Entity\Entity;
use TheFoxLab\TflSocial\Entities\Sync;
use TheFoxLab\TflSocial\Exceptions\RepositoryException;
use TheFoxLab\TflSocial\Repositories\SyncRepository;

final class SyncService
{
    public function __construct(
        private readonly SyncRepository $syncs = new SyncRepository()
    ) {
    }

    public function startSync(
        int|string|null $connectionId = null
        ): Sync
        {
            return $this->sync($this->syncs->insert([
                'social_connection_id' => $connectionId,
                'status' => Sync::STATUS_RUNNING,
                'started_at' => date('Y-m-d H:i:s'),
                'items_created' => 0,
                'items_updated' => 0,
                'items_failed' => 0,
            ]));
    }

    public function finishSync(
        int|string $syncId,
        ?string $message = null,
        int $itemsCreated = 0,
        int $itemsUpdated = 0,
        int $itemsFailed = 0
    ): Sync {
        return $this->completeSync(
            $syncId,
            Sync::STATUS_FINISHED,
            $message,
            $itemsCreated,
            $itemsUpdated,
            $itemsFailed
        );
    }

    public function failSync(
        int|string $syncId,
        string $message,
        int $itemsCreated = 0,
        int $itemsUpdated = 0,
        int $itemsFailed = 1
    ): Sync {
        return $this->completeSync(
            $syncId,
            Sync::STATUS_FAILED,
            $message,
            $itemsCreated,
            $itemsUpdated,
            $itemsFailed
        );
    }

    private function completeSync(
        int|string $syncId,
        string $status,
        ?string $message,
        int $itemsCreated,
        int $itemsUpdated,
        int $itemsFailed
    ): Sync
    {
        return $this->sync($this->syncs->update($syncId, [
            'status' => $status,
            'finished_at' => date('Y-m-d H:i:s'),
            'items_created' => $itemsCreated,
            'items_updated' => $itemsUpdated,
            'items_failed' => $itemsFailed,
            'message' => $message,
        ]));
    }

    public function getSync(int|string $syncId): ?Sync
    {
        $sync = $this->syncs->findById($syncId);

        return $sync === null ? null : $this->sync($sync);
    }

    private function sync(Entity $entity): Sync
    {
        if (! $entity instanceof Sync) {
            throw new RepositoryException('Sync repository returned an invalid entity.');
        }

        return $entity;
    }
}
