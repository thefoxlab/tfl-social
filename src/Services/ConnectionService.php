<?php

declare(strict_types=1);

namespace TheFoxLab\TflSocial\Services;

use CodeIgniter\Entity\Entity;
use JsonException;
use TheFoxLab\TflSocial\Entities\Connection;
use TheFoxLab\TflSocial\Exceptions\RepositoryException;
use TheFoxLab\TflSocial\Repositories\ConnectionRepository;

use function date;
use function json_decode;
use function json_encode;
use function sprintf;
use function strtotime;
use function time;

use const JSON_THROW_ON_ERROR;

final class ConnectionService
{
    public function __construct(
        private readonly ConnectionRepository $connections = new ConnectionRepository()
    ) {
    }

    /**
     * @param array<string, mixed> $metadata
     * @param list<string> $permissions
     *
     * @throws JsonException
     */
    public function connectProvider(
        int|string|null $accountId,
        string $provider,
        string $externalId,
        ?string $externalName = null,
        array $metadata = [],
        ?string $accessToken = null,
        ?string $refreshToken = null,
        ?string $tokenExpiresAt = null,
        array $permissions = [],
        int|string|null $parentConnectionId = null
    ): Connection {
        $data = [
            'social_account_id' => $accountId,
            'parent_connection_id' => $parentConnectionId,
            'provider' => $provider,
            'external_id' => $externalId,
            'external_name' => $externalName,
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'token_expires_at' => $tokenExpiresAt,
            'permissions' => $permissions === [] ? null : $this->encodeMetadata($permissions),
            'status' => 'active',
            'connected_at' => $this->now(),
            'metadata' => $metadata === [] ? null : $this->encodeMetadata($metadata),
        ];

        $existing = $this->connections->findByProviderExternalId($provider, $externalId);

        if ($existing !== null) {
            foreach ([
                'social_account_id',
                'parent_connection_id',
                'external_name',
                'refresh_token',
                'token_expires_at',
                'metadata',
            ] as $nullableField) {
                if ($data[$nullableField] === null) {
                    unset($data[$nullableField]);
                }
            }

            return $this->connection($this->connections->update($existing->social_connection_id, $data));
        }

        return $this->connection($this->connections->insert($data));
    }
    public function currentConnection(string $provider): ?Connection
    {
        $connection = $this->connections->findCurrentConnection($provider);
        
        return $connection === null ? null : $this->connection($connection);
    }
    public function disconnectProvider(int|string $connectionId): Connection
    {
        return $this->connection($this->connections->update($connectionId, [
            'status' => 'disconnected',
        ]));
    }

    public function disconnectProviderConnection(string $provider, string $externalId): Connection
    {
        $connection = $this->findProviderConnection($provider, $externalId);

        if ($connection === null) {
            throw new RepositoryException(sprintf(
                'Connection [%s:%s] was not found.',
                $provider,
                $externalId
            ));
        }

        return $this->disconnectProvider($connection->social_connection_id);
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
        return $this->updateTokens($connectionId, $accessToken);
    }

    /**
     * @param list<string> $permissions
     *
     * @throws JsonException
     */
    public function updateTokens(
        int|string $connectionId,
        string $accessToken,
        ?string $refreshToken = null,
        ?string $tokenExpiresAt = null,
        array $permissions = []
    ): Connection {
        $connection = $this->getConnection($connectionId);

        if ($connection === null) {
            throw new RepositoryException(sprintf('Connection [%s] was not found.', (string) $connectionId));
        }

        $data = [
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'token_expires_at' => $tokenExpiresAt,
        ];

        if ($permissions !== []) {
            $data['permissions'] = $this->encodeMetadata($permissions);
        }

        foreach (['refresh_token', 'token_expires_at'] as $nullableField) {
            if ($data[$nullableField] === null) {
                unset($data[$nullableField]);
            }
        }

        return $this->connection($this->connections->update($connectionId, $data));
    }

    public function updateStatus(int|string $connectionId, string $status): Connection
    {
        return $this->connection($this->connections->update($connectionId, [
            'status' => $status,
        ]));
    }

    public function updateLastSyncedAt(int|string $connectionId, ?string $syncedAt = null): Connection
    {
        return $this->connection($this->connections->update($connectionId, [
            'last_synced_at' => $syncedAt ?? $this->now(),
        ]));
    }

    public function getConnection(int|string $connectionId): ?Connection
    {
        $connection = $this->connections->findById($connectionId);

        return $connection === null ? null : $this->connection($connection);
    }

    public function findProviderConnection(string $provider, string $externalId): ?Connection
    {
        $connection = $this->connections->findByProviderExternalId($provider, $externalId);

        return $connection === null ? null : $this->connection($connection);
    }

    /**
     * @return list<Connection>
     */
    public function childConnections(int|string $connectionId): array
    {
        $connections = [];

        foreach ($this->connections->findByParentConnectionId($connectionId) as $connection) {
            $connections[] = $this->connection($connection);
        }

        return $connections;
    }

    public function isTokenExpired(Connection $connection): bool
    {
        $expiresAt = $connection->token_expires_at;

        if (! is_string($expiresAt) || $expiresAt === '') {
            return false;
        }

        $timestamp = strtotime($expiresAt);

        return $timestamp !== false && $timestamp <= time();
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

    private function now(): string
    {
        return date('Y-m-d H:i:s');
    }
}
