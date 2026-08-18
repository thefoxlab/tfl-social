<?php

declare(strict_types=1);

namespace TheFoxLab\TflSocial\Tests;

use InvalidArgumentException;
use TheFoxLab\TflSocial\Config\TflSocial as TflSocialConfig;
use TheFoxLab\TflSocial\Connector;
use TheFoxLab\TflSocial\Entities\Account;
use TheFoxLab\TflSocial\Entities\Connection;
use TheFoxLab\TflSocial\Http\ClientInterface;
use TheFoxLab\TflSocial\Http\Response;
use TheFoxLab\TflSocial\Repositories\AccountRepository;
use TheFoxLab\TflSocial\Repositories\ConnectionRepository;
use TheFoxLab\TflSocial\Services\AccountService;
use TheFoxLab\TflSocial\Services\ConnectionService;

final class ConnectorTest
{
    private int $assertions = 0;

    public function run(): void
    {
        echo "Running Connector Tests...\n\n";

        $this->testAccountAndProviderSetup();
        $this->testPageConnectionCreationAndTokenNullExpiry();
        $this->testInstagramConnectionCreationAndParentTokenInheritance();
        $this->testLiveGraphApiMethodsWithMockClient();
        $this->testMissingConnectionOrInvalidProviderThrowsException();

        echo "\nAll {$this->assertions} Connector assertions passed successfully!\n";
    }

    private function testAccountAndProviderSetup(): void
    {
        $connector = new Connector();

        $account = new Account(['social_account_id' => 10, 'name' => 'test_account']);
        $connector->account($account);
        $connector->provider('facebook');

        $this->assertTrue(true, 'Account and facebook provider set without error.');

        $caught = false;
        try {
            $connector->provider('unsupported_provider');
        } catch (InvalidArgumentException $e) {
            $caught = true;
            $this->assertTrue(str_contains($e->getMessage(), 'is not supported'), 'Exception message contains not supported.');
        }
        $this->assertTrue($caught, 'Unsupported provider throws InvalidArgumentException.');
    }

    private function testPageConnectionCreationAndTokenNullExpiry(): void
    {
        $mockClient = new class implements ClientInterface {
            public function get(string $uri, array $options = []): Response
            {
                if (str_contains($uri, '/me/accounts')) {
                    return new Response(200, json_encode([
                        'data' => [[
                            'id' => 'page_101',
                            'name' => 'Test Facebook Page',
                            'access_token' => 'EAAG_page_token_abc',
                            'category' => 'Business',
                            'tasks' => ['MANAGE', 'CREATE_CONTENT'],
                        ]],
                    ]) ?: '{}', []);
                }
                return new Response(200, '{}', []);
            }
            public function post(string $uri, array $options = []): Response { return new Response(200, '{}', []); }
            public function put(string $uri, array $options = []): Response { return new Response(200, '{}', []); }
            public function patch(string $uri, array $options = []): Response { return new Response(200, '{}', []); }
            public function delete(string $uri, array $options = []): Response { return new Response(200, '{}', []); }
        };

        $mockConnModel = new class extends \TheFoxLab\TflSocial\Models\ConnectionModel {
            public array $data = [];
            private array $whereCriteria = [];

            public function where($key, $value = null): static
            {
                if (is_array($key)) { $this->whereCriteria = array_merge($this->whereCriteria, $key); }
                else { $this->whereCriteria[$key] = $value; }
                return $this;
            }
            public function orderBy($column, $direction = 'ASC'): static { return $this; }
            public function first(): ?Connection
            {
                foreach ($this->data as $item) {
                    $match = true;
                    foreach ($this->whereCriteria as $k => $v) {
                        if ($item->{$k} != $v) { $match = false; break; }
                    }
                    if ($match) { $this->whereCriteria = []; return $item; }
                }
                $this->whereCriteria = [];
                return null;
            }
            public function find($id = null): ?Connection { return $this->data[$id] ?? null; }
            public function insert($data = null, bool $returnID = true): int|string|false
            {
                $id = count($this->data) + 1;
                $arr = is_array($data) ? $data : $data->toArray();
                $arr['social_connection_id'] = $id;
                $conn = new Connection($arr);
                $this->data[$id] = $conn;
                return $id;
            }
            public function update($id = null, $data = null): bool
            {
                $existing = $this->data[$id] ?? new Connection(['social_connection_id' => $id]);
                $arr = is_array($data) ? $data : $data->toArray();
                foreach ($arr as $k => $v) { $existing->{$k} = $v; }
                $this->data[$id] = $existing;
                return true;
            }
            public function errors(): array { return []; }
        };

        $connService = new ConnectionService(new ConnectionRepository($mockConnModel));
        $config = new TflSocialConfig();
        $config->providers['facebook']['appId'] = '123';
        $config->providers['facebook']['appSecret'] = 'secret';

        $connector = new Connector(config: $config, client: $mockClient, connectionService: $connService);
        $connector->account(new Account(['social_account_id' => 1]));
        $connector->provider('facebook');
        $connector->accessToken('user_token_xyz');

        $pages = $connector->pages();
        $this->assertEquals(1, count($pages), 'Pages collection contains 1 page.');

        $connection = $connector->connectPage('page_101');
        $this->assertEquals('facebook', $connection->provider, 'Connected provider is facebook.');
        $this->assertEquals('page_101', $connection->external_id, 'External ID matches.');
        $this->assertEquals('EAAG_page_token_abc', $connection->access_token, 'Access token matches page token.');
        $this->assertNull($connection->token_expires_at, 'Page connection token_expires_at is null.');
    }

    private function testInstagramConnectionCreationAndParentTokenInheritance(): void
    {
        $mockClient = new class implements ClientInterface {
            public function get(string $uri, array $options = []): Response
            {
                if (str_contains($uri, '/page_101')) {
                    return new Response(200, json_encode([
                        'id' => 'page_101',
                        'instagram_business_account' => [
                            'id' => 'ig_999',
                            'username' => 'test_ig_user',
                            'name' => 'Test IG Account',
                        ],
                    ]) ?: '{}', []);
                }
                return new Response(200, '{}', []);
            }
            public function post(string $uri, array $options = []): Response { return new Response(200, '{}', []); }
            public function put(string $uri, array $options = []): Response { return new Response(200, '{}', []); }
            public function patch(string $uri, array $options = []): Response { return new Response(200, '{}', []); }
            public function delete(string $uri, array $options = []): Response { return new Response(200, '{}', []); }
        };

        $mockConnModel = new class extends \TheFoxLab\TflSocial\Models\ConnectionModel {
            public array $data = [];
            private array $whereCriteria = [];

            public function where($key, $value = null): static
            {
                if (is_array($key)) { $this->whereCriteria = array_merge($this->whereCriteria, $key); }
                else { $this->whereCriteria[$key] = $value; }
                return $this;
            }
            public function orderBy($column, $direction = 'ASC'): static { return $this; }
            public function first(): ?Connection
            {
                foreach ($this->data as $item) {
                    $match = true;
                    foreach ($this->whereCriteria as $k => $v) {
                        if ($item->{$k} != $v) { $match = false; break; }
                    }
                    if ($match) { $this->whereCriteria = []; return $item; }
                }
                $this->whereCriteria = [];
                return null;
            }
            public function find($id = null): ?Connection { return $this->data[$id] ?? null; }
            public function insert($data = null, bool $returnID = true): int|string|false
            {
                $id = count($this->data) + 1;
                $arr = is_array($data) ? $data : $data->toArray();
                $arr['social_connection_id'] = $id;
                $conn = new Connection($arr);
                $this->data[$id] = $conn;
                return $id;
            }
            public function update($id = null, $data = null): bool
            {
                $existing = $this->data[$id] ?? new Connection(['social_connection_id' => $id]);
                $arr = is_array($data) ? $data : $data->toArray();
                foreach ($arr as $k => $v) { $existing->{$k} = $v; }
                $this->data[$id] = $existing;
                return true;
            }
            public function errors(): array { return []; }
        };

        $connService = new ConnectionService(new ConnectionRepository($mockConnModel));
        $config = new TflSocialConfig();

        $connector = new Connector(config: $config, client: $mockClient, connectionService: $connService);
        $connector->account(new Account(['social_account_id' => 1]));
        $connector->provider('facebook');

        // Setup parent page connection
        $pageConn = $connService->connectProvider(1, 'facebook', 'page_101', 'Page One', [], 'EAAG_parent_page_token', null, null);

        $igConnection = $connector->connectInstagramBusiness('ig_999');
        $this->assertEquals('instagram', $igConnection->provider, 'Instagram connection created.');
        $this->assertEquals('ig_999', $igConnection->external_id, 'External ID matches IG ID.');
        $this->assertEquals('EAAG_parent_page_token', $igConnection->access_token, 'Instagram inherited parent Facebook Page access token.');
        $this->assertNull($igConnection->token_expires_at, 'Instagram inherited null token_expires_at.');
    }

    private function testLiveGraphApiMethodsWithMockClient(): void
    {
        $mockClient = new class implements ClientInterface {
            public function get(string $uri, array $options = []): Response
            {
                if (str_contains($uri, '/feed')) {
                    return new Response(200, json_encode([
                        'data' => [
                            ['id' => 'post_1', 'message' => 'Post 1 Message'],
                            ['id' => 'post_2', 'message' => 'Post 2 Message'],
                        ],
                    ]) ?: '{}', []);
                }

                if (str_contains($uri, '/ig_999/media')) {
                    return new Response(200, json_encode([
                        'data' => [
                            ['id' => 'media_10', 'caption' => 'IG Media Caption', 'media_type' => 'IMAGE'],
                        ],
                    ]) ?: '{}', []);
                }

                return new Response(200, '{}', []);
            }
            public function post(string $uri, array $options = []): Response { return new Response(200, '{}', []); }
            public function put(string $uri, array $options = []): Response { return new Response(200, '{}', []); }
            public function patch(string $uri, array $options = []): Response { return new Response(200, '{}', []); }
            public function delete(string $uri, array $options = []): Response { return new Response(200, '{}', []); }
        };

        $mockConnModel = new class extends \TheFoxLab\TflSocial\Models\ConnectionModel {
            public array $data = [];
            public function where($key, $value = null): static { return $this; }
            public function orderBy($column, $direction = 'ASC'): static { return $this; }
            public function first(): ?Connection
            {
                return new Connection([
                    'social_connection_id' => 1,
                    'social_account_id' => 1,
                    'provider' => 'facebook',
                    'external_id' => 'page_101',
                    'access_token' => 'EAAG_valid_token',
                    'token_expires_at' => null,
                    'status' => Connection::STATUS_ACTIVE,
                ]);
            }
        };

        $connService = new ConnectionService(new ConnectionRepository($mockConnModel));
        $config = new TflSocialConfig();

        $connector = new Connector(config: $config, client: $mockClient, connectionService: $connService);
        $connector->account(new Account(['social_account_id' => 1]));
        $connector->provider('facebook');

        $feed = $connector->feed();
        $this->assertEquals(2, count($feed), 'Feed collection returns 2 items.');
    }

    private function testMissingConnectionOrInvalidProviderThrowsException(): void
    {
        $mockConnModel = new class extends \TheFoxLab\TflSocial\Models\ConnectionModel {
            public function where($key, $value = null): static { return $this; }
            public function orderBy($column, $direction = 'ASC'): static { return $this; }
            public function first(): ?Connection { return null; }
        };

        $connService = new ConnectionService(new ConnectionRepository($mockConnModel));
        $connector = new Connector(connectionService: $connService);
        $connector->account(new Account(['social_account_id' => 1]));
        $connector->provider('facebook');

        $caught = false;
        try {
            $connector->feed();
        } catch (InvalidArgumentException $e) {
            $caught = true;
            $this->assertTrue(str_contains($e->getMessage(), 'required'), 'Exception mentions required connection.');
        }
        $this->assertTrue($caught, 'Calling feed without active connection throws InvalidArgumentException.');
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

    private function assertNull(mixed $actual, string $message): void
    {
        $this->assertEquals(null, $actual, $message);
    }
}

if (basename(__FILE__) === basename($_SERVER['PHP_SELF'] ?? '')) {
    require_once __DIR__ . '/TokenLifecycleTest.php';
    (new ConnectorTest())->run();
}
