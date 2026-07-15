<?php

declare(strict_types=1);

namespace TheFoxLab\TflSocial;

use DateTimeImmutable;
use DateTimeInterface;
use JsonException;
use Throwable;
use TheFoxLab\TflSocial\Config\TflSocial;
use TheFoxLab\TflSocial\Contracts\SynchronizerInterface;
use TheFoxLab\TflSocial\Entities\Connection;
use TheFoxLab\TflSocial\Entities\Post;
use TheFoxLab\TflSocial\Http\Client;
use TheFoxLab\TflSocial\Http\ClientInterface;
use TheFoxLab\TflSocial\Providers\Facebook\GraphService as FacebookGraphService;
use TheFoxLab\TflSocial\Providers\Facebook\OAuth as FacebookOAuth;
use TheFoxLab\TflSocial\Providers\Instagram\GraphService as InstagramGraphService;
use TheFoxLab\TflSocial\Providers\Meta\GraphItem;
use TheFoxLab\TflSocial\Providers\Meta\GraphResponse;
use TheFoxLab\TflSocial\Services\ConnectionService;
use TheFoxLab\TflSocial\Services\MediaService;
use TheFoxLab\TflSocial\Services\PostService;
use TheFoxLab\TflSocial\Services\SyncService;

use function date;
use function in_array;
use function is_array;
use function is_int;
use function is_string;
use function json_encode;
use function strtolower;
use function trim;

use const JSON_THROW_ON_ERROR;

final class Synchronizer implements SynchronizerInterface
{
    private ?int $account = null;

    private ?int $connection = null;

    public function __construct(
        private readonly TflSocial $config = new TflSocial(),
        ?ClientInterface $client = null,
        private readonly ConnectionService $connections = new ConnectionService(),
        private readonly PostService $posts = new PostService(),
        private readonly MediaService $media = new MediaService(),
        private readonly SyncService $syncs = new SyncService()
    ) {
        $this->client = $client ?? new Client($this->config);
    }

    private readonly ClientInterface $client;

    public function account(int $account): self
    {
        $this->account = $account;
        $this->connection = null;

        return $this;
    }

    public function connection(int $connection): self
    {
        $this->connection = $connection;
        $this->account = null;

        return $this;
    }

    public function all(): void
    {
        $this->account = null;
        $this->connection = null;
        $this->run();
    }

    public function run(): void
    {
        foreach ($this->connectionsToSynchronize() as $connection) {
            $this->synchronizeConnection($connection);
        }
    }

    /**
     * @return list<Connection>
     */
    private function connectionsToSynchronize(): array
    {
        if ($this->connection !== null) {
            $connection = $this->connections->getConnection($this->connection);

            return $connection === null ? [] : [$connection];
        }

        return $this->connections->activeConnections($this->account);
    }
    private function synchronizeConnection(Connection $connection): void
    {
        $provider = $this->provider($connection);
        $sync = $this->syncs->startSync(connectionId: $this->connectionId($connection));
        
        $created = 0;
        $updated = 0;
        $failed = 0;
        
        try {
            $connection = $this->ensureValidToken($connection);
            
            foreach ($this->fetchNormalizedPosts($connection) as $post) {
                try {
                    $result = $this->posts->upsertPost($post['post']);
                    $this->media->syncMedia($result['post']->social_post_id, $post['media']);
                    
                    if ($result['created']) {
                        $created++;
                    } else {
                        $updated++;
                    }
                } catch (Throwable) {
                    $failed++;
                }
            }
            
            $this->connections->updateLastSyncedAt($this->connectionId($connection), $this->now());
            
            $this->syncs->finishSync(
                $sync->social_sync_id,
                'Synchronization completed.',
                $created,
                $updated,
                $failed
                );
        } catch (Throwable $exception) {
            $this->syncs->failSync(
                $sync->social_sync_id,
                $exception->getMessage(),
                $created,
                $updated,
                $failed + 1
                );
            
            throw $exception;
        }
    }
    
    private function ensureValidToken(Connection $connection): Connection
    {
        if (! $this->connections->isTokenExpired($connection)) {
            return $connection;
        }
        
        $provider = $this->provider($connection);
        
        if ($provider === 'instagram') {
            $parentId = $connection->parent_connection_id;
            
            if (is_int($parentId) || is_string($parentId)) {
                $parent = $this->connections->getConnection($parentId);
                
                if ($parent !== null) {
                    $parent = $this->ensureValidToken($parent);
                    
                    return $this->connections->updateTokens(
                        $this->connectionId($connection),
                        $this->stringValue($parent->access_token, 'Parent Facebook Page token is missing.'),
                        tokenExpiresAt: $this->nullableString($parent->token_expires_at)
                        );
                }
            }
        }
        
        if ($provider !== 'facebook') {
            return $connection;
        }
        
        $response = (new FacebookOAuth($this->config, $this->client))
        ->exchangeShortLivedTokenForLongLivedToken(
            $this->stringValue($connection->access_token, 'Facebook access token is missing.')
            );
        
        $refreshed = $this->connections->updateTokens(
            $this->connectionId($connection),
            $this->stringValue($response->accessToken(), 'Facebook token refresh did not return a token.'),
            tokenExpiresAt: $this->formatDateTime($response->expiresAt())
            );
        
        foreach ($this->connections->childConnections($this->connectionId($refreshed)) as $child) {
            if ($this->provider($child) === 'instagram') {
                $this->connections->updateTokens(
                    $this->connectionId($child),
                    $this->stringValue($refreshed->access_token, 'Facebook token refresh failed.'),
                    tokenExpiresAt: $this->nullableString($refreshed->token_expires_at)
                    );
            }
        }
        
        return $refreshed;
    }
    
    /**
     * @return list<array{post: array<string, mixed>, media: list<array<string, mixed>>}>
     */
    private function fetchNormalizedPosts(Connection $connection): array
    {
        return match ($this->provider($connection)) {
            'facebook' => $this->facebookPosts($connection),
            'instagram' => $this->instagramPosts($connection),
            default => [],
        };
    }
    
    
    /**
     * @return list<array{post: array<string, mixed>, media: list<array<string, mixed>>}>
     */
    private function facebookPosts(Connection $connection): array
    {
        $graph = new FacebookGraphService($this->config, $this->client);
        $items = [];

        $items[] = $this->normalizeFacebookProfile($connection, $graph->node($connection));

        foreach ($graph->edge($connection, 'feed') as $item) {
            $items[] = $this->normalizeFacebookFeedItem($connection, $item);
        }

        return $items;
    }

    /**
     * @return list<array{post: array<string, mixed>, media: list<array<string, mixed>>}>
     */
    private function instagramPosts(Connection $connection): array
    {
        $graph = new InstagramGraphService($this->config, $this->client);
        $items = [];

        $items[] = $this->normalizeInstagramProfile($connection, $graph->profile($connection));

        foreach ($graph->media($connection) as $item) {
            $items[] = $this->normalizeInstagramMediaItem($connection, $item);
        }

        return $items;
    }

    /**
     * @return array{post: array<string, mixed>, media: list<array<string, mixed>>}
     */
    private function normalizeFacebookProfile(Connection $connection, GraphResponse $response): array
    {
        $payload = $response->toArray();
        $externalId = $this->stringValue($payload['id'] ?? null, 'Facebook profile id is missing.');

        return [
            'post' => $this->postData(
                $connection,
                'profile:' . $externalId,
                'profile',
                $this->nullableString($payload['name'] ?? null),
                null,
                null,
                [],
                $payload
            ),
            'media' => $this->mediaFromPicture($payload['picture'] ?? null),
        ];
    }

    /**
     * @return array{post: array<string, mixed>, media: list<array<string, mixed>>}
     */
    private function normalizeFacebookFeedItem(Connection $connection, GraphItem $item): array
    {
        $payload = $item->toArray();
        $externalId = $this->stringValue($payload['id'] ?? null, 'Facebook post id is missing.');

        return [
            'post' => $this->postData(
                $connection,
                $externalId,
                $this->nullableString($payload['type'] ?? null) ?? 'post',
                $this->nullableString($payload['message'] ?? null) ?? $this->nullableString($payload['story'] ?? null),
                $this->nullableString($payload['permalink_url'] ?? null),
                $this->dateTimeString($payload['created_time'] ?? null),
                $this->facebookMetrics($payload),
                $payload
            ),
            'media' => $this->facebookMedia($payload),
        ];
    }

    /**
     * @return array{post: array<string, mixed>, media: list<array<string, mixed>>}
     */
    private function normalizeInstagramProfile(Connection $connection, GraphResponse $response): array
    {
        $payload = $response->toArray();
        $externalId = $this->stringValue($payload['id'] ?? null, 'Instagram profile id is missing.');

        return [
            'post' => $this->postData(
                $connection,
                'profile:' . $externalId,
                'profile',
                $this->nullableString($payload['username'] ?? null),
                null,
                null,
                $this->instagramProfileMetrics($payload),
                $payload
            ),
            'media' => $this->mediaFromUrl($this->nullableString($payload['profile_picture_url'] ?? null), 'image'),
        ];
    }

    /**
     * @return array{post: array<string, mixed>, media: list<array<string, mixed>>}
     */
    private function normalizeInstagramMediaItem(Connection $connection, GraphItem $item): array
    {
        $payload = $item->toArray();
        $externalId = $this->stringValue($payload['id'] ?? null, 'Instagram media id is missing.');
        $type = strtolower($this->nullableString($payload['media_type'] ?? null) ?? 'media');

        return [
            'post' => $this->postData(
                $connection,
                $externalId,
                $type,
                $this->nullableString($payload['caption'] ?? null),
                $this->nullableString($payload['permalink'] ?? null),
                $this->dateTimeString($payload['timestamp'] ?? null),
                $this->instagramMediaMetrics($payload),
                $payload
            ),
            'media' => $this->instagramMedia($payload),
        ];
    }

    /**
     * @param array<string, mixed> $metrics
     * @param array<string, mixed> $raw
     *
     * @return array<string, mixed>
     */
    private function postData(
        Connection $connection,
        string $externalId,
        ?string $type,
        ?string $message,
        ?string $permalink,
        ?string $publishedAt,
        array $metrics,
        array $raw
        ): array
        {
            return [
                'social_connection_id' => $this->connectionId($connection),
                'provider' => $this->provider($connection),
                'external_id' => $externalId,
                'parent_external_id' => $this->nullableString($raw['parent_id'] ?? null),
                'type' => $type,
                'message' => $message,
                'permalink' => $permalink,
                'published_at' => $publishedAt,
                'sync_time' => $this->now(),
                'metrics' => $this->json($metrics),
                'raw_json' => $this->json($raw),
                'status' => Post::STATUS_ACTIVE,
            ];
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<string, int>
     */
    private function facebookMetrics(array $payload): array
    {
        $metrics = [];
        $shares = $payload['shares']['count'] ?? null;

        if (is_int($shares)) {
            $metrics['shares'] = $shares;
        }

        return $metrics;
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<string, int>
     */
    private function instagramProfileMetrics(array $payload): array
    {
        return $this->integerMetrics($payload, ['followers_count', 'follows_count', 'media_count']);
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<string, int>
     */
    private function instagramMediaMetrics(array $payload): array
    {
        return $this->integerMetrics($payload, ['like_count', 'comments_count']);
    }

    /**
     * @param array<string, mixed> $payload
     * @param list<string> $keys
     *
     * @return array<string, int>
     */
    private function integerMetrics(array $payload, array $keys): array
    {
        $metrics = [];

        foreach ($keys as $key) {
            if (is_int($payload[$key] ?? null)) {
                $metrics[$key] = $payload[$key];
            }
        }

        return $metrics;
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return list<array<string, mixed>>
     */
    private function facebookMedia(array $payload): array
    {
        $media = [];
    
        if (! empty($payload['attachments']['data']) && is_array($payload['attachments']['data'])) {
            foreach ($payload['attachments']['data'] as $attachment) {
                if (! is_array($attachment)) {
                    continue;
                }
    
                $type = strtolower($this->nullableString($attachment['media_type'] ?? null) ?? 'image');
    
                $url = $this->nullableString(
                    $attachment['media']['image']['src'] ?? null
                );
    
                if ($url === null) {
                    $url = $this->nullableString($attachment['unshimmed_url'] ?? null);
                }
    
                if ($url === null) {
                    continue;
                }
    
                $media[] = [
                    'type' => $type,
                    'url' => $url,
                    'thumbnail_url' => $this->nullableString($attachment['media']['image']['src'] ?? null),
                    'alt_text' => $this->nullableString($attachment['title'] ?? null),
                    'sort_order' => count($media),
                    'metadata' => $this->json($attachment),
                ];
            }
        }
    
        if ($media !== []) {
            return $media;
        }
    
        foreach (['full_picture', 'picture', 'source'] as $key) {
            $url = $this->nullableString($payload[$key] ?? null);
    
            if ($url !== null) {
                $media[] = $this->mediaData(
                    $url,
                    $key === 'source' ? 'video' : 'image',
                    null,
                    $media
                );
            }
        }
    
        return $media;
    }
    /**
     * @param array<string, mixed> $payload
     *
     * @return list<array<string, mixed>>
     */
    private function instagramMedia(array $payload): array
    {
        $url = $this->nullableString($payload['media_url'] ?? null);
        
        if ($url === null) {
            return [];
        }
        
        $type = strtolower($this->nullableString($payload['media_type'] ?? null) ?? 'image');
        
        return [[
            'type' => in_array($type, ['video', 'reels'], true) ? 'video' : 'image',
            'url' => $url,
            'thumbnail_url' => $this->nullableString($payload['thumbnail_url'] ?? null),
            'alt_text' => $this->nullableString($payload['caption'] ?? null),
            'sort_order' => 0,
            'metadata' => $this->json($payload),
        ]];
    }
    
    /**
     * @return list<array<string, mixed>>
     */
    private function mediaFromPicture(mixed $picture): array
    {
        if (! is_array($picture) || ! is_array($picture['data'] ?? null)) {
            return [];
        }

        return $this->mediaFromUrl($this->nullableString($picture['data']['url'] ?? null), 'image');
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function mediaFromUrl(
        ?string $url,
        string $type,
        ?string $thumbnail = null,
        ?string $altText = null,
        array $metadata = []
        ): array
        {
            if ($url === null) {
                return [];
            }
            
            return [
                $this->mediaData(
                    $url,
                    $type,
                    $thumbnail,
                    [],
                    $altText,
                    $metadata
                    ),
            ];
    }

    /**
         * @param list<array<string, mixed>> $existing
         *
         * @return array<string, mixed>
         */
        private function mediaData(
            string $url,
            string $type,
            ?string $thumbnail,
            array $existing,
            ?string $altText = null,
            array $metadata = []
        ): array
        {
            return [
                'type' => $type,
                'url' => $url,
                'thumbnail_url' => $thumbnail,
                'alt_text' => $altText,
                'sort_order' => count($existing),
                'metadata' => $this->json($metadata),
            ];
        }

    private function provider(Connection $connection): string
    {
        return $this->stringValue($connection->provider, 'Connection provider is missing.');
    }

    private function connectionId(Connection $connection): int|string
    {
        $connectionId = $connection->social_connection_id;

        if (! is_int($connectionId) && ! is_string($connectionId)) {
            throw new \InvalidArgumentException('Connection id is missing.');
        }

        return $connectionId;
    }

    private function stringValue(mixed $value, string $message): string
    {
        if (! is_string($value) || trim($value) === '') {
            throw new \InvalidArgumentException($message);
        }

        return $value;
    }

    private function nullableString(mixed $value): ?string
    {
        return is_string($value) && trim($value) !== '' ? $value : null;
    }

    private function dateTimeString(mixed $value): ?string
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            return (new DateTimeImmutable($value))->format('Y-m-d H:i:s');
        } catch (Throwable) {
            return null;
        }
    }

    private function formatDateTime(?DateTimeInterface $dateTime): ?string
    {
        return $dateTime?->format('Y-m-d H:i:s');
    }

    private function now(): string
    {
        return date('Y-m-d H:i:s');
    }

    /**
     * @param array<mixed> $data
     */
    private function json(array $data): string
    {
        try {
            return json_encode($data, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return '{}';
        }
    }
}
