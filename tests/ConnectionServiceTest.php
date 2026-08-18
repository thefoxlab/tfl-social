<?php

declare(strict_types=1);

namespace TheFoxLab\TflSocial\Tests;

use DateTimeImmutable;
use TheFoxLab\TflSocial\Entities\Connection;
use TheFoxLab\TflSocial\Repositories\ConnectionRepository;
use TheFoxLab\TflSocial\Services\ConnectionService;

final class ConnectionServiceTest
{
    private int $assertions = 0;

    public function run(): void
    {
        echo "Running ConnectionService Tests...\n\n";

        $this->testTokenExpiryEvaluation();
        $this->test300SecondExpiryBuffer();
        $this->testNullAndEmptyExpiryEvaluation();
        $this->testConnectionStatusTransitions();
        $this->testParentChildConnectionRelationships();
        $this->testConnectProviderInsertAndUpdate();

        echo "\nAll {$this->assertions} ConnectionService assertions passed successfully!\n";
    }

    private function testTokenExpiryEvaluation(): void
    {
        $service = new ConnectionService();

        // Expired 100 seconds ago
        $past = new Connection([
            'token_expires_at' => (new DateTimeImmutable('-100 seconds'))->format('Y-m-d H:i:s'),
        ]);
        $this->assertTrue($service->isTokenExpired($past), 'Token in the past is expired.');

        // Expires in 10,000 seconds
        $future = new Connection([
            'token_expires_at' => (new DateTimeImmutable('+10000 seconds'))->format('Y-m-d H:i:s'),
        ]);
        $this->assertFalse($service->isTokenExpired($future), 'Token far in the future is not expired.');
    }

    private function test300SecondExpiryBuffer(): void
    {
        $service = new ConnectionService();

        // Expires in 250 seconds (inside 300s buffer)
        $insideBuffer = new Connection([
            'token_expires_at' => (new DateTimeImmutable('+250 seconds'))->format('Y-m-d H:i:s'),
        ]);
        $this->assertTrue($service->isTokenExpired($insideBuffer), 'Token expiring in 250s is inside 300s safety buffer.');

        // Expires in 350 seconds (outside 300s buffer)
        $outsideBuffer = new Connection([
            'token_expires_at' => (new DateTimeImmutable('+350 seconds'))->format('Y-m-d H:i:s'),
        ]);
        $this->assertFalse($service->isTokenExpired($outsideBuffer), 'Token expiring in 350s is outside 300s safety buffer.');
    }

    private function testNullAndEmptyExpiryEvaluation(): void
    {
        $service = new ConnectionService();

        $nullExpiry = new Connection(['token_expires_at' => null]);
        $this->assertFalse($service->isTokenExpired($nullExpiry), 'Null expiry returns false.');

        $emptyExpiry = new Connection(['token_expires_at' => '']);
        $this->assertFalse($service->isTokenExpired($emptyExpiry), 'Empty string expiry returns false.');
    }

    private function testConnectionStatusTransitions(): void
    {
        $connection = new Connection([
            'social_connection_id' => 1,
            'status' => Connection::STATUS_ACTIVE,
        ]);

        $this->assertEquals(Connection::STATUS_ACTIVE, $connection->status, 'Initial status is active.');

        $connection->status = Connection::STATUS_INACTIVE;
        $this->assertEquals(Connection::STATUS_INACTIVE, $connection->status, 'Status updated to inactive.');

        $connection->status = 'disconnected';
        $this->assertEquals('disconnected', $connection->status, 'Status updated to disconnected.');
    }

    private function testParentChildConnectionRelationships(): void
    {
        $parent = new Connection([
            'social_connection_id' => 10,
            'provider' => 'facebook',
            'external_id' => 'page_123',
            'access_token' => 'EAAG_parent_token',
        ]);

        $child = new Connection([
            'social_connection_id' => 11,
            'parent_connection_id' => 10,
            'provider' => 'instagram',
            'external_id' => 'ig_456',
            'access_token' => $parent->access_token,
        ]);

        $this->assertEquals($parent->social_connection_id, $child->parent_connection_id, 'Child references parent_connection_id.');
        $this->assertEquals($parent->access_token, $child->access_token, 'Child shares parent access token.');
    }

    private function testConnectProviderInsertAndUpdate(): void
    {
        $mockModel = new class extends \TheFoxLab\TflSocial\Models\ConnectionModel {
            /** @var array<int|string, Connection> */
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

            public function first(): ?Connection
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

            public function find($id = null): ?Connection
            {
                return $this->data[$id] ?? null;
            }

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

        $repo = new ConnectionRepository($mockModel);
        $service = new ConnectionService($repo);

        // Insert new connection
        $c1 = $service->connectProvider(1, 'facebook', 'page_1', 'Page One', [], 'token_1', null, null);
        $this->assertEquals(1, $c1->social_connection_id, 'New connection receives ID 1.');
        $this->assertEquals('Page One', $c1->external_name, 'External name matches.');
        $this->assertNull($c1->token_expires_at, 'Token expires at is null.');

        // Update existing connection
        $c2 = $service->connectProvider(1, 'facebook', 'page_1', 'Page One Updated', [], 'token_2', null, null);
        $this->assertEquals(1, $c2->social_connection_id, 'Updated connection keeps ID 1.');
        $this->assertEquals('Page One Updated', $c2->external_name, 'External name updated.');
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

    private function assertNull(mixed $actual, string $message): void
    {
        $this->assertEquals(null, $actual, $message);
    }
}

if (basename(__FILE__) === basename($_SERVER['PHP_SELF'] ?? '')) {
    require_once __DIR__ . '/TokenLifecycleTest.php';
    (new ConnectionServiceTest())->run();
}
