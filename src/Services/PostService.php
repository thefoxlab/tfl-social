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

    private function post(Entity $entity): Post
    {
        if (! $entity instanceof Post) {
            throw new RepositoryException('Post repository returned an invalid entity.');
        }

        return $entity;
    }
}
