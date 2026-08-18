<?php

declare(strict_types=1);

namespace TheFoxLab\TflSocial\Tests;

use TheFoxLab\TflSocial\Entities\Media;
use TheFoxLab\TflSocial\Entities\Post;
use TheFoxLab\TflSocial\Exceptions\RepositoryException;
use TheFoxLab\TflSocial\Repositories\MediaRepository;
use TheFoxLab\TflSocial\Repositories\PostRepository;
use TheFoxLab\TflSocial\Services\MediaService;
use TheFoxLab\TflSocial\Services\PostService;

final class PostServiceTest
{
    private int $assertions = 0;

    public function run(): void
    {
        echo "Running PostService Tests...\n\n";

        $this->testStoreAndUpdatePost();
        $this->testUpsertNewAndExistingPost();
        $this->testUpsertMissingRequiredFieldsThrowsException();
        $this->testMediaSyncAndSortOrderHandling();

        echo "\nAll {$this->assertions} PostService assertions passed successfully!\n";
    }

    private function testStoreAndUpdatePost(): void
    {
        $mockPostModel = new class extends \TheFoxLab\TflSocial\Models\PostModel {
            public array $data = [];

            public function find($id = null): ?Post
            {
                return $this->data[$id] ?? null;
            }

            public function insert($data = null, bool $returnID = true): int|string|false
            {
                $id = count($this->data) + 1;
                $arr = is_array($data) ? $data : $data->toArray();
                $arr['social_post_id'] = $id;
                $this->data[$id] = new Post($arr);
                return $id;
            }

            public function update($id = null, $data = null): bool
            {
                $existing = $this->data[$id] ?? new Post(['social_post_id' => $id]);
                $arr = is_array($data) ? $data : $data->toArray();
                foreach ($arr as $k => $v) {
                    $existing->{$k} = $v;
                }
                $this->data[$id] = $existing;
                return true;
            }

            public function errors(): array
            {
                return [];
            }
        };

        $service = new PostService(new PostRepository($mockPostModel));

        $post = $service->storePost([
            'social_connection_id' => 1,
            'provider' => 'facebook',
            'external_id' => 'fb_post_100',
            'message' => 'Hello World',
        ]);

        $this->assertEquals(1, $post->social_post_id, 'Stored post receives ID 1.');
        $this->assertEquals('Hello World', $post->message, 'Message matches.');

        $updated = $service->updatePost(1, [
            'message' => 'Hello Updated',
        ]);

        $this->assertEquals('Hello Updated', $updated->message, 'Post message updated.');
    }

    private function testUpsertNewAndExistingPost(): void
    {
        $mockPostModel = new class extends \TheFoxLab\TflSocial\Models\PostModel {
            public array $data = [];
            private array $whereCriteria = [];

            public function where($key, $value = null): static
            {
                if (is_array($key)) {
                    $this->whereCriteria = $key;
                } else {
                    $this->whereCriteria[$key] = $value;
                }
                return $this;
            }

            public function orderBy($column, $direction = 'ASC'): static
            {
                return $this;
            }

            public function first(): ?Post
            {
                foreach ($this->data as $item) {
                    $match = true;
                    foreach ($this->whereCriteria as $k => $v) {
                        if ($item->{$k} != $v) {
                            $match = false;
                            break;
                        }
                    }
                    if ($match) {
                        $this->whereCriteria = [];
                        return $item;
                    }
                }
                $this->whereCriteria = [];
                return null;
            }

            public function find($id = null): ?Post
            {
                return $this->data[$id] ?? null;
            }

            public function insert($data = null, bool $returnID = true): int|string|false
            {
                $id = count($this->data) + 1;
                $arr = is_array($data) ? $data : $data->toArray();
                $arr['social_post_id'] = $id;
                $this->data[$id] = new Post($arr);
                return $id;
            }

            public function update($id = null, $data = null): bool
            {
                $existing = $this->data[$id] ?? new Post(['social_post_id' => $id]);
                $arr = is_array($data) ? $data : $data->toArray();
                foreach ($arr as $k => $v) {
                    $existing->{$k} = $v;
                }
                $this->data[$id] = $existing;
                return true;
            }

            public function errors(): array
            {
                return [];
            }
        };

        $service = new PostService(new PostRepository($mockPostModel));

        // Initial UPSERT (Creates new post)
        $res1 = $service->upsertPost([
            'social_connection_id' => 5,
            'external_id' => 'post_ext_1',
            'message' => 'First Insert',
        ]);

        $this->assertTrue($res1['created'], 'UPSERT returned created = true for new post.');
        $this->assertEquals(1, $res1['post']->social_post_id, 'Post ID is 1.');
        $this->assertEquals('First Insert', $res1['post']->message, 'Message is First Insert.');

        // Second UPSERT (Updates existing post)
        $res2 = $service->upsertPost([
            'social_connection_id' => 5,
            'external_id' => 'post_ext_1',
            'message' => 'Second Update',
        ]);

        $this->assertFalse($res2['created'], 'UPSERT returned created = false for existing post.');
        $this->assertEquals(1, $res2['post']->social_post_id, 'Post ID remains 1.');
        $this->assertEquals('Second Update', $res2['post']->message, 'Message updated to Second Update.');
    }

    private function testUpsertMissingRequiredFieldsThrowsException(): void
    {
        $service = new PostService(new PostRepository(new class extends \TheFoxLab\TflSocial\Models\PostModel {}));

        $caught = false;
        try {
            $service->upsertPost([
                'external_id' => 'no_conn_id',
            ]);
        } catch (RepositoryException $e) {
            $caught = true;
            $this->assertEquals('Post connection id is required for UPSERT.', $e->getMessage(), 'Exception message matches missing connection ID.');
        }
        $this->assertTrue($caught, 'Missing connection ID throws RepositoryException.');

        $caught2 = false;
        try {
            $service->upsertPost([
                'social_connection_id' => 1,
                'external_id' => '',
            ]);
        } catch (RepositoryException $e) {
            $caught2 = true;
            $this->assertEquals('Post external id is required for UPSERT.', $e->getMessage(), 'Exception message matches empty external ID.');
        }
        $this->assertTrue($caught2, 'Missing external ID throws RepositoryException.');
    }

    private function testMediaSyncAndSortOrderHandling(): void
    {
        $mockMediaModel = new class extends \TheFoxLab\TflSocial\Models\MediaModel {
            public array $data = [];
            private array $whereCriteria = [];

            public function where($key, $value = null): static
            {
                if (is_array($key)) {
                    $this->whereCriteria = array_merge($this->whereCriteria, $key);
                } else {
                    $this->whereCriteria[$key] = $value;
                }
                return $this;
            }

            public function orderBy($column, $direction = 'ASC'): static
            {
                return $this;
            }

            public function first(): ?Media
            {
                foreach ($this->data as $item) {
                    $match = true;
                    foreach ($this->whereCriteria as $k => $v) {
                        if ($item->{$k} != $v) {
                            $match = false;
                            break;
                        }
                    }
                    if ($match) {
                        $this->whereCriteria = [];
                        return $item;
                    }
                }
                $this->whereCriteria = [];
                return null;
            }

            public function findAll(?int $limit = null, int $offset = 0): array
            {
                $results = [];
                foreach ($this->data as $item) {
                    $match = true;
                    foreach ($this->whereCriteria as $k => $v) {
                        if ($item->{$k} != $v) {
                            $match = false;
                            break;
                        }
                    }
                    if ($match) {
                        $results[] = $item;
                    }
                }
                $this->whereCriteria = [];
                return $results;
            }

            public function find($id = null): ?Media
            {
                return $this->data[$id] ?? null;
            }

            public function insert($data = null, bool $returnID = true): int|string|false
            {
                $id = count($this->data) + 1;
                $arr = is_array($data) ? $data : $data->toArray();
                $arr['social_media_id'] = $id;
                $this->data[$id] = new Media($arr);
                return $id;
            }

            public function update($id = null, $data = null): bool
            {
                $existing = $this->data[$id] ?? new Media(['social_media_id' => $id]);
                $arr = is_array($data) ? $data : $data->toArray();
                foreach ($arr as $k => $v) {
                    $existing->{$k} = $v;
                }
                $this->data[$id] = $existing;
                return true;
            }

            public function delete($id = null, bool $purge = false): bool
            {
                unset($this->data[$id]);
                return true;
            }

            public function errors(): array
            {
                return [];
            }
        };

        $mediaService = new MediaService(new MediaRepository($mockMediaModel));

        // Sync initial media items (order 0 and 1)
        $mediaService->syncMedia(100, [
            ['type' => 'image', 'url' => 'http://img1.jpg', 'sort_order' => 0],
            ['type' => 'image', 'url' => 'http://img2.jpg', 'sort_order' => 1],
        ]);

        $this->assertEquals(2, count($mockMediaModel->data), '2 media items synced.');

        // Sync update with only 1 item (item 1 detached)
        $mediaService->syncMedia(100, [
            ['type' => 'image', 'url' => 'http://img1_updated.jpg', 'sort_order' => 0],
        ]);

        $this->assertEquals(1, count($mockMediaModel->data), '1 media item remains (sort_order 1 detached).');
        $this->assertEquals('http://img1_updated.jpg', reset($mockMediaModel->data)->url, 'Remaining media item URL updated.');
    }

    private function assertEquals(mixed $expected, mixed $actual, string $message): void
    {
        $this->assertions++;
        if ($expected !== $actual) {
            throw new \RuntimeException("Assertion Failed: {$message} (Expected " . var_export($expected, true) . ", got " . var_export($actual, true) . ")");
        }
        echo "  [OK] {$message}\n";
    }

    private function assertFalse(mixed $actual, string $message): void
    {
        $this->assertEquals(false, $actual, $message);
    }

    private function assertTrue(mixed $actual, string $message): void
    {
        $this->assertEquals(true, $actual, $message);
    }
}

if (basename(__FILE__) === basename($_SERVER['PHP_SELF'] ?? '')) {
    require_once __DIR__ . '/TokenLifecycleTest.php';
    (new PostServiceTest())->run();
}
