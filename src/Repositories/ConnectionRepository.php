<?php

declare(strict_types=1);

namespace TheFoxLab\TflSocial\Repositories;

use CodeIgniter\Entity\Entity;
use TheFoxLab\TflSocial\Models\ConnectionModel;

final class ConnectionRepository extends AbstractRepository
{
    public function __construct(?ConnectionModel $model = null)
    {
        parent::__construct($model ?? new ConnectionModel(), 'social_connection_id');
    }

    public function findByProviderExternalId(string $provider, string $externalId): ?Entity
    {
        return $this->findOne([
            'provider' => $provider,
            'external_id' => $externalId,
        ]);
    }
}
