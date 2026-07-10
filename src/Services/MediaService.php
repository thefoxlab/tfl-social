<?php

declare(strict_types=1);

namespace TheFoxLab\TflSocial\Services;

use CodeIgniter\Entity\Entity;
use TheFoxLab\TflSocial\Entities\Media;
use TheFoxLab\TflSocial\Exceptions\RepositoryException;
use TheFoxLab\TflSocial\Repositories\MediaRepository;

final class MediaService
{
    public function __construct(
        private readonly MediaRepository $media = new MediaRepository()
    ) {
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

    private function media(Entity $entity): Media
    {
        if (! $entity instanceof Media) {
            throw new RepositoryException('Media repository returned an invalid entity.');
        }

        return $entity;
    }
}
