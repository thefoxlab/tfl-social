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
        int|string|null $accountId = null,
        int|string|null $connectionId = null,
        ?string $provider = null
    ): Sync {
        return $this->sync($this->syncs->insert([
            'social_account_id' => $accountId,
            'social_connection_id' => $connectionId,
            'provider' => $provider,
            'status' => 'running',
            'started_at' => date('Y-m-d H:i:s'),
        ]));
    }

    public function finishSync(int|string $syncId, ?string $message = null): Sync
    {
        return $this->sync($this->syncs->update($syncId, [
            'status' => 'finished',
            'finished_at' => date('Y-m-d H:i:s'),
            'message' => $message,
        ]));
    }

    public function failSync(int|string $syncId, string $message): Sync
    {
        return $this->sync($this->syncs->update($syncId, [
            'status' => 'failed',
            'finished_at' => date('Y-m-d H:i:s'),
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
