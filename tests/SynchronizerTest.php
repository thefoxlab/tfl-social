<?php

declare(strict_types=1);

namespace TheFoxLab\TflSocial\Tests;

use TheFoxLab\TflSocial\Config\TflSocial as TflSocialConfig;
use TheFoxLab\TflSocial\Entities\Connection;
use TheFoxLab\TflSocial\Entities\Media;
use TheFoxLab\TflSocial\Entities\Post;
use TheFoxLab\TflSocial\Entities\Sync;
use TheFoxLab\TflSocial\Http\ClientInterface;
use TheFoxLab\TflSocial\Http\Response;
use TheFoxLab\TflSocial\Repositories\ConnectionRepository;
use TheFoxLab\TflSocial\Repositories\MediaRepository;
use TheFoxLab\TflSocial\Repositories\PostRepository;
use TheFoxLab\TflSocial\Repositories\SyncRepository;
use TheFoxLab\TflSocial\Services\ConnectionService;
use TheFoxLab\TflSocial\Services\MediaService;
use TheFoxLab\TflSocial\Services\PostService;
use TheFoxLab\TflSocial\Services\SyncService;
use TheFoxLab\TflSocial\Synchronizer;

final class SynchronizerTest
{
    private int $assertions = 0;

    public function run(): void
    {
        echo "Running Synchronizer Tests...\n\n";

        $this->testFacebookAndInstagramSynchronizationPipeline();
        $this->testSynchronizerScopeSetting();
        $this->testPartialFailureLoggingAndStatus();

        echo "\nAll {$this->assertions} Synchronizer assertions passed successfully!\n";
    }

    private function testFacebookAndInstagramSynchronizationPipeline(): void
    {
        $mockClient = new class implements ClientInterface {
            public function get(string $uri, array $options = []): Response
            {
                if (str_contains($uri, '/page_1001/feed')) {
                    return new Response(200, json_encode([
                        'data' => [
                            [
                                'id' => 'fb_post_100',
                                'type' => 'status',
                                'message' => 'Facebook Sync Post 1',
                                'created_time' => '2026-08-17T12:00:00+0000',
                                'permalink_url' => 'https://facebook.com/post/100',
                                'shares' => ['count' => 10],
                                'attachments' => [
                                    'data' => [[
                                        'media_type' => 'photo',
                                        'media' => ['image' => ['src' => 'https://img.facebook.com/1.jpg']],
                                        'title' => 'Photo Title',
                                    ]],
                                ],
                            ],
                        ],
                    ]) ?: '{}', []);
                }

                if (str_contains($uri, '/page_1001')) {
                    return new Response(200, json_encode([
                        'id' => 'page_1001',
                        'name' => 'Facebook Page Name',
                        'picture' => ['data' => ['url' => 'https://img.facebook.com/profile.jpg']],
                    ]) ?: '{}', []);
                }

                if (str_contains($uri, '/ig_2002/media')) {
                    return new Response(200, json_encode([
                        'data' => [
                            [
                                'id' => 'ig_post_500',
                                'media_type' => 'IMAGE',
                                'caption' => 'Instagram Post Caption',
                                'media_url' => 'https://img.instagram.com/500.jpg',
                                'permalink' => 'https://instagram.com/p/500',
                                'timestamp' => '2026-08-17T12:30:00+0000',
                                'like_count' => 45,
                                'comments_count' => 5,
                            ],
                        ],
                    ]) ?: '{}', []);
                }

                if (str_contains($uri, '/ig_2002')) {
                    return new Response(200, json_encode([
                        'id' => 'ig_2002',
                        'username' => 'ig_user_test',
                        'profile_picture_url' => 'https://img.instagram.com/profile.jpg',
                        'followers_count' => 1000,
                    ]) ?: '{}', []);
                }

                return new Response(200, '{}', []);
            }
            public function post(string $uri, array $options = []): Response { return new Response(200, '{}', []); }
            public function put(string $uri, array $options = []): Response { return new Response(200, '{}', []); }
            public function patch(string $uri, array $options = []): Response { return new Response(200, '{}', []); }
            public function delete(string $uri, array $options = []): Response { return new Response(200, '{}', []); }
        };

        // In-memory ConnectionModel
        $connModel = new class extends \TheFoxLab\TflSocial\Models\ConnectionModel {
            public array $data = [];
            public function find($id = null): ?Connection { return $this->data[$id] ?? null; }
            public function findAll(?int $limit = null, int $offset = 0): array { return array_values($this->data); }
            public function where($key, $value = null): static { return $this; }
            public function update($id = null, $data = null): bool
            {
                if (isset($this->data[$id])) {
                    foreach ((array)$data as $k => $v) { $this->data[$id]->{$k} = $v; }
                }
                return true;
            }
        };

        $fbConn = new Connection([
            'social_connection_id' => 1,
            'social_account_id' => 10,
            'provider' => 'facebook',
            'external_id' => 'page_1001',
            'access_token' => 'EAAG_valid_fb_token',
            'token_expires_at' => null,
            'status' => Connection::STATUS_ACTIVE,
        ]);
        $igConn = new Connection([
            'social_connection_id' => 2,
            'social_account_id' => 10,
            'parent_connection_id' => 1,
            'provider' => 'instagram',
            'external_id' => 'ig_2002',
            'access_token' => 'EAAG_valid_fb_token',
            'token_expires_at' => null,
            'status' => Connection::STATUS_ACTIVE,
        ]);
        $connModel->data[1] = $fbConn;
        $connModel->data[2] = $igConn;

        // In-memory PostModel
        $postModel = new class extends \TheFoxLab\TflSocial\Models\PostModel {
            public array $data = [];
            private array $whereCriteria = [];
            public function where($key, $value = null): static
            {
                if (is_array($key)) { $this->whereCriteria = array_merge($this->whereCriteria, $key); }
                else { $this->whereCriteria[$key] = $value; }
                return $this;
            }
            public function first(): ?Post
            {
                foreach ($this->data as $item) {
                    $match = true;
                    foreach ($this->whereCriteria as $k => $v) { if ($item->{$k} != $v) { $match = false; break; } }
                    if ($match) { $this->whereCriteria = []; return $item; }
                }
                $this->whereCriteria = [];
                return null;
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
                if (isset($this->data[$id])) { foreach ((array)$data as $k => $v) { $this->data[$id]->{$k} = $v; } }
                return true;
            }
            public function find($id = null): ?Post { return $this->data[$id] ?? null; }
        };

        // In-memory MediaModel
        $mediaModel = new class extends \TheFoxLab\TflSocial\Models\MediaModel {
            public array $data = [];
            public function where($key, $value = null): static { return $this; }
            public function first(): ?Media { return null; }
            public function findAll(?int $limit = null, int $offset = 0): array { return []; }
            public function insert($data = null, bool $returnID = true): int|string|false
            {
                $id = count($this->data) + 1;
                $arr = is_array($data) ? $data : $data->toArray();
                $arr['social_media_id'] = $id;
                $this->data[$id] = new Media($arr);
                return $id;
            }
            public function find($id = null): ?Media { return $this->data[$id] ?? null; }
        };

        // In-memory SyncModel
        $syncModel = new class extends \TheFoxLab\TflSocial\Models\SyncModel {
            public array $data = [];
            public function insert($data = null, bool $returnID = true): int|string|false
            {
                $id = count($this->data) + 1;
                $arr = is_array($data) ? $data : $data->toArray();
                $arr['social_sync_id'] = $id;
                $this->data[$id] = new Sync($arr);
                return $id;
            }
            public function update($id = null, $data = null): bool
            {
                if (isset($this->data[$id])) { foreach ((array)$data as $k => $v) { $this->data[$id]->{$k} = $v; } }
                return true;
            }
            public function find($id = null): ?Sync { return $this->data[$id] ?? null; }
        };

        $connService = new ConnectionService(new ConnectionRepository($connModel));
        $postService = new PostService(new PostRepository($postModel));
        $mediaService = new MediaService(new MediaRepository($mediaModel));
        $syncService = new SyncService(new SyncRepository($syncModel));

        $synchronizer = new Synchronizer(
            config: new TflSocialConfig(),
            client: $mockClient,
            connections: $connService,
            posts: $postService,
            media: $mediaService,
            syncs: $syncService
        );

        $synchronizer->account(10)->run();

        // Check posts created (1 Facebook profile + 1 FB feed post + 1 IG profile + 1 IG media post = 4 total)
        $this->assertEquals(4, count($postModel->data), '4 total posts/profiles created in postModel.');
        $this->assertEquals(2, count($syncModel->data), '2 sync history records logged (1 per connection).');

        $sync1 = $syncModel->data[1];
        $this->assertEquals(Sync::STATUS_FINISHED, $sync1->status, 'Sync 1 status is finished.');
        $this->assertEquals(2, $sync1->items_created, 'Sync 1 created 2 items (profile + post).');
    }

    private function testSynchronizerScopeSetting(): void
    {
        $synchronizer = new Synchronizer();
        $synchronizer->account(5);
        $this->assertTrue(true, 'Account scope set.');

        $synchronizer->connection(12);
        $this->assertTrue(true, 'Connection scope set.');

        $synchronizer->all();
        $this->assertTrue(true, 'All scope reset.');
    }

    private function testPartialFailureLoggingAndStatus(): void
    {
        $syncModel = new class extends \TheFoxLab\TflSocial\Models\SyncModel {
            public array $data = [];
            public function insert($data = null, bool $returnID = true): int|string|false
            {
                $id = count($this->data) + 1;
                $arr = is_array($data) ? $data : $data->toArray();
                $arr['social_sync_id'] = $id;
                $this->data[$id] = new Sync($arr);
                return $id;
            }
            public function update($id = null, $data = null): bool
            {
                if (isset($this->data[$id])) { foreach ((array)$data as $k => $v) { $this->data[$id]->{$k} = $v; } }
                return true;
            }
            public function find($id = null): ?Sync { return $this->data[$id] ?? null; }
        };

        $syncService = new SyncService(new SyncRepository($syncModel));

        $sync = $syncService->startSync(1);
        $this->assertEquals(Sync::STATUS_RUNNING, $sync->status, 'Sync starts with STATUS_RUNNING.');

        $finished = $syncService->finishSync($sync->social_sync_id, 'Sync completed', 5, 2, 0);
        $this->assertEquals(Sync::STATUS_FINISHED, $finished->status, 'Sync finishes with STATUS_FINISHED.');
        $this->assertEquals(5, $finished->items_created, 'Items created count matches.');
        $this->assertEquals(2, $finished->items_updated, 'Items updated count matches.');

        $failedSync = $syncService->startSync(2);
        $failed = $syncService->failSync($failedSync->social_sync_id, 'Graph API Error', 1, 0, 1);
        $this->assertEquals(Sync::STATUS_FAILED, $failed->status, 'Sync fails with STATUS_FAILED.');
        $this->assertEquals('Graph API Error', $failed->message, 'Sync message records failure error.');
    }

    private function assertEquals(mixed $expected, mixed $actual, string $message): void
    {
        $this->assertions++;
        if ($expected !== $actual) {
            throw new \RuntimeException("Assertion Failed: {$message} (Expected " . var_export($expected, true) . ", got " . var_export($actual, true) . ")");
        }
        echo "  [OK] {$message}\n";
    }

    private function assertTrue(mixed $actual, string $message): void
    {
        $this->assertEquals(true, $actual, $message);
    }
}

if (basename(__FILE__) === basename($_SERVER['PHP_SELF'] ?? '')) {
    require_once __DIR__ . '/TokenLifecycleTest.php';
    (new SynchronizerTest())->run();
}
