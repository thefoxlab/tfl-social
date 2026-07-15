<?php

declare(strict_types=1);

namespace TheFoxLab\TflSocial\Services;

use CodeIgniter\Entity\Entity;
use TheFoxLab\TflSocial\Entities\Post;
use TheFoxLab\TflSocial\Exceptions\RepositoryException;
use TheFoxLab\TflSocial\Repositories\PostRepository;

final class PostService
{
    public function __construct(
        private readonly PostRepository $posts = new PostRepository()
    ) {
    }

    /**
     * @param array<string, mixed>|Post $data
     */
    public function storePost(array|Post $data): Post
    {
        return $this->post($this->posts->insert($data));
    }

    /**
     * @param array<string, mixed>|Post $data
     */
    public function updatePost(int|string $postId, array|Post $data): Post
    {
        return $this->post($this->posts->update($postId, $data));
    }

    public function deletePost(int|string $postId): void
    {
        $this->posts->delete($postId);
    }

    public function getPost(int|string $postId): ?Post
    {
        $post = $this->posts->findById($postId);

        return $post === null ? null : $this->post($post);
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array{post: Post, created: bool}
     */
    public function upsertPost(array $data): array
    {
        $connectionId = $data['social_connection_id'] ?? null;
        $externalId = $data['external_id'] ?? null;

        if (! is_int($connectionId) && ! is_string($connectionId)) {
            throw new RepositoryException('Post connection id is required for UPSERT.');
        }

        if (! is_string($externalId) || $externalId === '') {
            throw new RepositoryException('Post external id is required for UPSERT.');
        }

        $existing = $this->posts->findByConnectionExternalId($connectionId, $externalId);

        if ($existing === null) {
            return [
                'post' => $this->storePost($data),
                'created' => true,
            ];
        }

        return [
            'post' => $this->updatePost($existing->social_post_id, $data),
            'created' => false,
        ];
    }

    private function post(Entity $entity): Post
    {
        if (! $entity instanceof Post) {
            throw new RepositoryException('Post repository returned an invalid entity.');
        }

        return $entity;
    }
}
