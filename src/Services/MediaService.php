<?php

declare(strict_types=1);

namespace TheFoxLab\TflSocial\Services;

use CodeIgniter\Entity\Entity;
use TheFoxLab\TflSocial\Config\TflSocial;
use TheFoxLab\TflSocial\Entities\Connection;
use TheFoxLab\TflSocial\Entities\Media;
use TheFoxLab\TflSocial\Entities\Post;
use TheFoxLab\TflSocial\Exceptions\RepositoryException;
use TheFoxLab\TflSocial\Providers\Facebook\GraphService as FacebookGraphService;
use TheFoxLab\TflSocial\Providers\Instagram\GraphService as InstagramGraphService;
use TheFoxLab\TflSocial\Repositories\ConnectionRepository;
use TheFoxLab\TflSocial\Repositories\MediaRepository;
use TheFoxLab\TflSocial\Repositories\PostRepository;
use Throwable;

use function date;
use function hexdec;
use function is_array;
use function is_int;
use function is_object;
use function is_string;
use function json_decode;
use function json_encode;
use function preg_match;
use function str_contains;
use function strtolower;
use function time;

final class MediaService
{
    public function __construct(
        private readonly MediaRepository $media = new MediaRepository(),
        private ?PostRepository $posts = null,
        private ?ConnectionRepository $connections = null,
        private ?TflSocial $config = null,
        private ?FacebookGraphService $facebookGraph = null,
        private ?InstagramGraphService $instagramGraph = null
    ) {
        $this->posts ??= new PostRepository();
        $this->connections ??= new ConnectionRepository();
        $this->config = TflSocial::resolve($this->config);
    }

    /**
     * @param array<string, mixed>|Media $data
     */
    public function attachMedia(int|string $postId, array|Media $data): Media
    {
        if (is_array($data)) {
            $data['social_post_id'] = $postId;
        } else {
            $data->social_post_id = $postId;
        }

        return $this->media($this->media->insert($data));
    }

    public function detachMedia(int|string $mediaId): void
    {
        $this->media->delete($mediaId);
    }

    public function getMedia(int|string $mediaId): ?Media
    {
        $media = $this->media->findById($mediaId);

        return $media === null ? null : $this->media($media);
    }

    /**
     * @param list<array<string, mixed>> $items
     */
    public function syncMedia(int|string $postId, array $items): void
    {
        $seen = [];

        foreach ($items as $index => $item) {
            $sortOrder = is_int($item['sort_order'] ?? null) ? $item['sort_order'] : $index;
            $item['social_post_id'] = $postId;
            $item['sort_order'] = $sortOrder;
            $seen[$sortOrder] = true;

            $item = $this->enrichMediaMetadata($item, $sortOrder);

            $existing = $this->media->findByPostSortOrder($postId, $sortOrder);

            if ($existing === null) {
                $this->attachMedia($postId, $item);

                continue;
            }

            $this->media($this->media->update($existing->social_media_id, $item));
        }

        foreach ($this->media->findByPostId($postId) as $media) {
            $sortOrder = $media->sort_order;

            if ((is_int($sortOrder) || is_string($sortOrder)) && isset($seen[(int) $sortOrder])) {
                continue;
            }

            $this->detachMedia($media->social_media_id);
        }
    }

    /**
     * Enriches media metadata with Meta reference ID and URL expiry timestamp.
     *
     * @param array<string, mixed> $item
     * @return array<string, mixed>
     */
    public function enrichMediaMetadata(array $item, ?int $sortOrder = null): array
    {
        $meta = [];
        if (! empty($item['metadata'])) {
            $meta = is_array($item['metadata'])
                ? $item['metadata']
                : (json_decode((string) $item['metadata'], true) ?? []);
        }

        if (empty($meta['meta_id'])) {
            $metaId = $meta['target']['id'] ?? $meta['id'] ?? null;
            if (! empty($metaId)) {
                $meta['meta_id'] = (string) $metaId;
            }
        }

        $url = $item['url'] ?? null;
        if ($url !== null) {
            $expiry = self::extractExpiry($url);
            if ($expiry !== null) {
                $meta['url_expires_at'] = date('Y-m-d H:i:s', $expiry);
                $meta['url_expires_timestamp'] = $expiry;
            }
        }

        $item['metadata'] = json_encode($meta);
        $item['media_refresh_at'] = self::calculateMediaRefreshAt($url);

        return $item;
    }

    /**
     * Calculates the scheduled refresh datetime for a media URL (24 hours before expiration).
     */
    public static function calculateMediaRefreshAt(?string $url, int $leadSeconds = 86400): ?string
    {
        if ($url === null || $url === '') {
            return date('Y-m-d H:i:s');
        }

        if (! self::isMetaCdnUrl($url)) {
            return null;
        }

        $expiry = self::extractExpiry($url);
        if ($expiry === null) {
            return date('Y-m-d H:i:s');
        }

        $refreshTimestamp = $expiry - $leadSeconds;
        $now = time();

        if ($refreshTimestamp <= $now) {
            return date('Y-m-d H:i:s', $now);
        }

        return date('Y-m-d H:i:s', $refreshTimestamp);
    }

    /**
     * Checks if a URL originates from Meta (Facebook/Instagram) CDN.
     */
    public static function isMetaCdnUrl(?string $url): bool
    {
        if ($url === null || $url === '') {
            return false;
        }

        $lower = strtolower($url);

        return str_contains($lower, 'fbcdn.net')
            || str_contains($lower, 'cdninstagram.com');
    }

    /**
     * Extracts unix timestamp from Meta signed CDN URL ('oe' query parameter).
     */
    public static function extractExpiry(?string $url): ?int
    {
        if ($url === null || $url === '') {
            return null;
        }

        if (preg_match('/[?&]oe=([a-fA-F0-9]+)/', $url, $matches)) {
            return (int) hexdec($matches[1]);
        }

        return null;
    }

    /**
     * Checks if a media URL is expired or within the expiry buffer.
     */
    public static function isExpired(?string $url, int $bufferSeconds = 300): bool
    {
        if ($url === null || $url === '') {
            return true;
        }

        if (! self::isMetaCdnUrl($url)) {
            return false;
        }

        $expiry = self::extractExpiry($url);
        if ($expiry === null) {
            return true;
        }

        return $expiry <= (time() + $bufferSeconds);
    }

    /**
     * Refreshes an expired media record's CDN URL from Meta Graph API.
     *
     * @param Media|array<string, mixed> $media
     * @return array{url: string, thumbnail_url: ?string}|null Fresh URLs, or null if refresh failed or not needed.
     */
    public function refreshMediaRecord(
        Media|array $media,
        ?Connection $connection = null,
        ?Post $post = null
    ): ?array {
        $url = is_object($media) ? $media->url : ($media['url'] ?? null);
        $thumbnailUrl = is_object($media) ? $media->thumbnail_url : ($media['thumbnail_url'] ?? null);

        $refreshAt = is_object($media) ? $media->media_refresh_at : ($media['media_refresh_at'] ?? null);
        $isDue = ($refreshAt !== null && strtotime((string) $refreshAt) <= time());

        if (! $isDue && ! self::isExpired($url, 86400)) {
            return [
                'url'           => (string) $url,
                'thumbnail_url' => $thumbnailUrl !== null ? (string) $thumbnailUrl : null,
            ];
        }

        $postId = is_object($media) ? $media->social_post_id : ($media['social_post_id'] ?? null);
        if ($postId === null) {
            return null;
        }

        if ($post === null) {
            $postEntity = $this->posts->findById($postId);
            if (! $postEntity instanceof Post) {
                return null;
            }
            $post = $postEntity;
        }

        if ($connection === null) {
            $connId = $post->social_connection_id;
            if ($connId === null) {
                return null;
            }
            $connEntity = $this->connections->findById($connId);
            if (! $connEntity instanceof Connection) {
                return null;
            }
            $connection = $connEntity;
        }

        $token = $connection->access_token;
        if (empty($token)) {
            return null;
        }

        $provider = $connection->provider;
        $mediaMeta = [];
        $metaRaw = is_object($media) ? $media->metadata : ($media['metadata'] ?? null);
        if (! empty($metaRaw)) {
            $mediaMeta = is_array($metaRaw) ? $metaRaw : (json_decode((string) $metaRaw, true) ?? []);
        }

        $sortOrder = (int) (is_object($media) ? ($media->sort_order ?? 0) : ($media['sort_order'] ?? 0));
        $freshUrl = null;
        $freshThumb = null;
        $metaId = $mediaMeta['meta_id'] ?? null;

        try {
            if ($provider === 'instagram') {
                if ($post->type === 'profile') {
                    $response = $this->instagramGraph()->profile($connection);
                    $data = $response->toArray();
                    $freshUrl = $data['profile_picture_url'] ?? null;
                    $metaId = $data['id'] ?? $metaId;
                } else {
                    $igMediaId = (string) ($metaId ?? $mediaMeta['id'] ?? $post->external_id);
                    $response = $this->instagramGraph()->mediaById($connection, $igMediaId);
                    $data = $response->toArray();
                    $freshUrl = $data['media_url'] ?? null;
                    $freshThumb = $data['thumbnail_url'] ?? null;
                    $metaId = $data['id'] ?? $igMediaId;
                }
            } elseif ($provider === 'facebook') {
                if ($post->type === 'profile') {
                    $response = $this->facebookGraph()->node($connection);
                    $data = $response->toArray();
                    $freshUrl = $data['picture']['data']['url'] ?? null;
                    $metaId = $data['id'] ?? $metaId;
                } else {
                    $fbPostId = (string) $post->external_id;
                    $response = $this->facebookGraph()->postById($connection, $fbPostId);
                    $data = $response->toArray();
                    $targetId = $mediaMeta['target']['id'] ?? $mediaMeta['meta_id'] ?? null;

                    if (! empty($data['attachments']['data']) && is_array($data['attachments']['data'])) {
                        $attachments = $data['attachments']['data'];
                        if ($targetId !== null) {
                            foreach ($attachments as $att) {
                                if (($att['target']['id'] ?? null) === $targetId) {
                                    $freshUrl = $att['media']['image']['src'] ?? $att['unshimmed_url'] ?? null;
                                    $freshThumb = $att['media']['image']['src'] ?? null;
                                    $metaId = $att['target']['id'] ?? $targetId;
                                    break;
                                }
                            }
                        }

                        if ($freshUrl === null && isset($attachments[$sortOrder])) {
                            $att = $attachments[$sortOrder];
                            $freshUrl = $att['media']['image']['src'] ?? $att['unshimmed_url'] ?? null;
                            $freshThumb = $att['media']['image']['src'] ?? null;
                            $metaId = $att['target']['id'] ?? $metaId;
                        }
                    }

                    if ($freshUrl === null) {
                        $freshUrl = $data['full_picture'] ?? null;
                        $freshThumb = $data['full_picture'] ?? null;
                        $metaId = $data['id'] ?? $metaId;
                    }
                }
            }
        } catch (Throwable $e) {
            $mediaMeta['last_refresh_error'] = $e->getMessage();
            $mediaMeta['last_refresh_attempt'] = date('Y-m-d H:i:s');
            $mediaId = is_object($media) ? $media->social_media_id : ($media['social_media_id'] ?? null);
            if ($mediaId !== null) {
                $this->media->update($mediaId, ['metadata' => json_encode($mediaMeta)]);
            }

            return null;
        }

        if ($freshUrl !== null) {
            $expiry = self::extractExpiry($freshUrl);
            if ($metaId !== null) {
                $mediaMeta['meta_id'] = (string) $metaId;
            }
            if ($expiry !== null) {
                $mediaMeta['url_expires_at'] = date('Y-m-d H:i:s', $expiry);
                $mediaMeta['url_expires_timestamp'] = $expiry;
            }
            $mediaMeta['last_refreshed_at'] = date('Y-m-d H:i:s');
            unset($mediaMeta['last_refresh_error']);

            $updateData = [
                'url'              => $freshUrl,
                'thumbnail_url'    => $freshThumb ?? $thumbnailUrl,
                'metadata'         => json_encode($mediaMeta),
                'media_refresh_at' => self::calculateMediaRefreshAt($freshUrl),
            ];

            $mediaId = is_object($media) ? $media->social_media_id : ($media['social_media_id'] ?? null);
            if ($mediaId !== null) {
                $this->media->update($mediaId, $updateData);
            }

            return [
                'url'           => $freshUrl,
                'thumbnail_url' => $freshThumb ?? $thumbnailUrl,
            ];
        }

        return null;
    }

    /**
     * Refreshes media records due for refresh (scheduled at or before NOW()) for a given connection.
     */
    public function refreshExpiredMediaForConnection(Connection $connection, int $limit = 50): int
    {
        $connId = $connection->social_connection_id;
        if ($connId === null) {
            return 0;
        }

        $dueMedia = $this->media->findDueForRefresh($connId, $limit);
        if ($dueMedia === []) {
            return 0;
        }

        $refreshedCount = 0;
        foreach ($dueMedia as $m) {
            try {
                $result = $this->refreshMediaRecord($m, $connection);
                if ($result !== null) {
                    $refreshedCount++;
                }
            } catch (Throwable) {
                // Individual item failure never halts connection sync
            }
        }

        return $refreshedCount;
    }

    private function facebookGraph(): FacebookGraphService
    {
        return $this->facebookGraph ??= new FacebookGraphService($this->config);
    }

    private function instagramGraph(): InstagramGraphService
    {
        return $this->instagramGraph ??= new InstagramGraphService($this->config);
    }

    private function media(Entity $entity): Media
    {
        if (! $entity instanceof Media) {
            throw new RepositoryException('Media repository returned an invalid entity.');
        }

        return $entity;
    }
}
