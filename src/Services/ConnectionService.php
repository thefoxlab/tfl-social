<?php

declare(strict_types=1);

namespace TheFoxLab\TflSocial\Services;

use CodeIgniter\Entity\Entity;
use JsonException;
use TheFoxLab\TflSocial\Entities\Connection;
use TheFoxLab\TflSocial\Exceptions\RepositoryException;
use TheFoxLab\TflSocial\Repositories\ConnectionRepository;

use function json_decode;
use function json_encode;

use const JSON_THROW_ON_ERROR;

final class ConnectionService
{
    public function __construct(
        private readonly ConnectionRepository $connections = new ConnectionRepository()
    ) {
    }

    /**
     * @param array<string, mixed> $metadata
     *
     * @throws JsonException
     */
    public function connectProvider(
        int|string $accountId,
        string $provider,
        string $externalId,
        ?string $name = null,
        array $metadata = []
    ): Connection {
        return $this->connection($this->connections->insert([
            'social_account_id' => $accountId,
            'provider' => $provider,
            'external_id' => $externalId,
            'name' => $name,
            'status' => 'active',
            'metadata' => $metadata === [] ? null : $this->encodeMetadata($metadata),
        ]));
    }

    public function disconnectProvider(int|string $connectionId): Connection
    {
        return $this->connection($this->connections->update($connectionId, [
            'status' => 'disconnected',
        ]));
    }

    /**
     * @throws JsonException
     */
    public function saveAccessToken(int|string $connectionId, string $accessToken): Connection
    {
        return $this->updateAccessToken($connectionId, $accessToken);
    }

    /**
     * @throws JsonException
     */
    public function updateAccessToken(int|string $connectionId, string $accessToken): Connection
    {
        $connection = $this->getConnection($connectionId);

        if ($connection === null) {
            throw new RepositoryException(sprintf('Connection [%s] was not found.', (string) $connectionId));
        }

        $metadata = $this->decodeMetadata($connection->metadata);
        $metadata['access_token'] = $accessToken;

        return $this->connection($this->connections->update($connectionId, [
            'metadata' => $this->encodeMetadata($metadata),
        ]));
    }

    public function getConnection(int|string $connectionId): ?Connection
    {
        $connection = $this->connections->findById($connectionId);

        return $connection === null ? null : $this->connection($connection);
    }

    private function connection(Entity $entity): Connection
    {
        if (! $entity instanceof Connection) {
            throw new RepositoryException('Connection repository returned an invalid entity.');
        }

        return $entity;
    }

    /**
     * @param array<string, mixed> $metadata
     *
     * @throws JsonException
     */
    private function encodeMetadata(array $metadata): string
    {
        return json_encode($metadata, JSON_THROW_ON_ERROR);
    }

    /**
     * @return array<string, mixed>
     *
     * @throws JsonException
     */
    private function decodeMetadata(mixed $metadata): array
    {
        if (! is_string($metadata) || $metadata === '') {
            return [];
        }

        $decoded = json_decode($metadata, true, 512, JSON_THROW_ON_ERROR);

        return is_array($decoded) ? $decoded : [];
    }
}
