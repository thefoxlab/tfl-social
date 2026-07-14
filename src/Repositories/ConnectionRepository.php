<?php

declare(strict_types=1);

namespace TheFoxLab\TflSocial\Repositories;

use CodeIgniter\Entity\Entity;
use TheFoxLab\TflSocial\Entities\Connection;
use TheFoxLab\TflSocial\Models\ConnectionModel;

final class ConnectionRepository extends AbstractRepository
{
    public function __construct(?ConnectionModel $model = null)
    {
        parent::__construct($model ?? new ConnectionModel(), 'social_connection_id');
    }

    public function findByAccountProviderExternalId(int|string $accountId, string $provider, string $externalId): ?Entity
    {
        return $this->findOne([
            'social_account_id' => $accountId,
            'provider' => $provider,
            'external_id' => $externalId,
        ]);
    }

    public function findCurrentConnection(int|string $accountId, string $provider): ?Entity
    {
        return $this->findOne(
            [
                'social_account_id' => $accountId,
                'provider' => $provider,
                'status' => Connection::STATUS_ACTIVE,
            ],
            'connected_at DESC'
        );
    }

    /**
     * @return list<Entity>
     */
    public function findByParentConnectionId(int|string $connectionId): array
    {
        return $this->findAll([
            'parent_connection_id' => $connectionId,
        ]);
    }
}
