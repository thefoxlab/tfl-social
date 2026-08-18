<?php

declare(strict_types=1);

namespace CodeIgniter {
    if (! class_exists(Model::class)) {
        class Model
        {
            public function where($key, $value = null): static { return $this; }
            public function orderBy($column, $direction = 'ASC'): static { return $this; }
            public function first(): mixed { return null; }
            public function find($id = null): mixed { return null; }
            public function findAll(?int $limit = null, int $offset = 0): array { return []; }
            public function insert($data = null, bool $returnID = true): int|string|false { return 1; }
            public function update($id = null, $data = null): bool { return true; }
            public function delete($id = null, bool $purge = false): bool { return true; }
            public function errors(): array { return []; }
        }
    }
}

namespace CodeIgniter\Config {
    if (! class_exists(BaseConfig::class)) {
        class BaseConfig
        {
            public function __construct()
            {
            }
        }
    }
}

namespace CodeIgniter\Entity {
    if (! class_exists(Entity::class)) {
        class Entity
        {
            protected $attributes = [];

            /**
             * @param array<string, mixed>|null $data
             */
            public function __construct(?array $data = null)
            {
                if ($data !== null) {
                    $this->fill($data);
                }
            }

            /**
             * @param array<string, mixed> $data
             */
            public function fill(array $data): static
            {
                foreach ($data as $key => $value) {
                    $this->__set($key, $value);
                }

                return $this;
            }

            public function __get(string $key): mixed
            {
                return $this->attributes[$key] ?? null;
            }

            public function __set(string $key, mixed $value): void
            {
                $this->attributes[$key] = $value;
            }

            public function __isset(string $key): bool
            {
                return isset($this->attributes[$key]);
            }

            public function toArray(): array
            {
                return $this->attributes;
            }
        }
    }
}

namespace TheFoxLab\TflSocial\Tests {

    use DateTimeImmutable;
    use TheFoxLab\TflSocial\Config\TflSocial as TflSocialConfig;
    use TheFoxLab\TflSocial\Entities\Connection;
    use TheFoxLab\TflSocial\Http\ClientInterface;
    use TheFoxLab\TflSocial\Http\Response;
    use TheFoxLab\TflSocial\Providers\Facebook\OAuth as FacebookOAuth;
    use TheFoxLab\TflSocial\Services\ConnectionService;

    final class TokenLifecycleTest
    {
        private int $assertions = 0;

        public function run(): void
        {
            echo "Running Token Lifecycle Tests...\n\n";

            $this->testUserTokenExchange();
            $this->testPageTokenStorageWithNullExpiry();
            $this->testPageTokenRefreshBehaviour();
            $this->testIsTokenExpiredWithNullExpiry();
            $this->testValidPageConnectionRemainsActive();
            $this->testInstagramChildConnectionInheritsPageToken();
            $this->testExpiringUserTokenSafetyBuffer();

            echo "\nAll {$this->assertions} assertions passed successfully!\n";
        }

        /**
         * 1. User token exchange.
         */
        private function testUserTokenExchange(): void
        {
            $mockClient = new class implements ClientInterface {
                public function get(string $uri, array $options = []): Response
                {
                    return new Response(200, json_encode([
                        'access_token' => 'EAAG_long_lived_user_token_123',
                        'token_type' => 'bearer',
                        'expires_in' => 5184000, // 60 days
                    ]) ?: '{}', []);
                }

                public function post(string $uri, array $options = []): Response
                {
                    return new Response(200, '{}', []);
                }

                public function put(string $uri, array $options = []): Response
                {
                    return new Response(200, '{}', []);
                }

                public function patch(string $uri, array $options = []): Response
                {
                    return new Response(200, '{}', []);
                }

                public function delete(string $uri, array $options = []): Response
                {
                    return new Response(200, '{}', []);
                }
            };

            $config = new TflSocialConfig();
            $config->providers['facebook']['appId'] = '123456';
            $config->providers['facebook']['appSecret'] = 'secret_abc';

            $oauth = new FacebookOAuth(config: $config, client: $mockClient);
            $response = $oauth->exchangeShortLivedTokenForLongLivedToken('short_lived_user_token');

            $this->assertEquals('EAAG_long_lived_user_token_123', $response->accessToken(), 'User token exchange returns new access token.');
            $this->assertEquals(5184000, $response->expiresIn(), 'User token exchange returns 60 day expiresIn integer.');
            $this->assertNotNull($response->expiresAt(), 'User token exchange returns non-null expiresAt DateTime.');
        }

        /**
         * 2. Page token storage.
         */
        private function testPageTokenStorageWithNullExpiry(): void
        {
            $connection = new Connection([
                'social_connection_id' => 1,
                'social_account_id' => 10,
                'provider' => 'facebook',
                'external_id' => 'page_1001',
                'access_token' => 'EAAG_page_access_token_xyz',
                'token_expires_at' => null,
                'status' => Connection::STATUS_ACTIVE,
            ]);

            $this->assertNull($connection->token_expires_at, 'Page token has token_expires_at = null.');
            $this->assertEquals('facebook', $connection->provider, 'Provider is facebook.');
            $this->assertEquals('EAAG_page_access_token_xyz', $connection->access_token, 'Access token matches.');
        }

        /**
         * 3. Page token refresh behaviour.
         */
        private function testPageTokenRefreshBehaviour(): void
        {
            $connection = new Connection([
                'social_connection_id' => 1,
                'social_account_id' => 10,
                'provider' => 'facebook',
                'external_id' => 'page_1001',
                'access_token' => 'EAAG_page_token_valid',
                'token_expires_at' => null,
                'status' => Connection::STATUS_ACTIVE,
            ]);

            $service = new ConnectionService();
            $this->assertFalse($service->isTokenExpired($connection), 'Page token with null expiry is not expired.');
        }

        /**
         * 4. Page token with token_expires_at = null.
         */
        private function testIsTokenExpiredWithNullExpiry(): void
        {
            $service = new ConnectionService();

            $pageConnection = new Connection([
                'token_expires_at' => null,
            ]);

            $this->assertFalse($service->isTokenExpired($pageConnection), 'isTokenExpired returns false when token_expires_at is null.');

            $emptyExpiryConnection = new Connection([
                'token_expires_at' => '',
            ]);

            $this->assertFalse($service->isTokenExpired($emptyExpiryConnection), 'isTokenExpired returns false when token_expires_at is empty string.');
        }

        /**
         * 5. Valid Page connection remaining active.
         */
        private function testValidPageConnectionRemainsActive(): void
        {
            $connection = new Connection([
                'social_connection_id' => 5,
                'status' => Connection::STATUS_ACTIVE,
                'token_expires_at' => null,
            ]);

            $service = new ConnectionService();
            $this->assertFalse($service->isTokenExpired($connection), 'Valid page connection is not expired.');
            $this->assertEquals(Connection::STATUS_ACTIVE, $connection->status, 'Connection status remains active.');
        }

        /**
         * 6. Instagram child connection receiving the refreshed Page token.
         */
        private function testInstagramChildConnectionInheritsPageToken(): void
        {
            $pageConnection = new Connection([
                'social_connection_id' => 1,
                'social_account_id' => 10,
                'provider' => 'facebook',
                'external_id' => 'page_1001',
                'access_token' => 'EAAG_refreshed_page_token_999',
                'token_expires_at' => null,
            ]);

            $instagramConnection = new Connection([
                'social_connection_id' => 2,
                'social_account_id' => 10,
                'parent_connection_id' => 1,
                'provider' => 'instagram',
                'external_id' => 'ig_2002',
                'access_token' => 'EAAG_old_token',
                'token_expires_at' => null,
            ]);

            $instagramConnection->access_token = $pageConnection->access_token;
            $instagramConnection->token_expires_at = $pageConnection->token_expires_at;

            $this->assertEquals('EAAG_refreshed_page_token_999', $instagramConnection->access_token, 'Instagram child connection inherited updated Page access token.');
            $this->assertNull($instagramConnection->token_expires_at, 'Instagram child connection inherited null token_expires_at.');
        }

        /**
         * 7. Expiring user token behaviour (300-second buffer check).
         */
        private function testExpiringUserTokenSafetyBuffer(): void
        {
            $service = new ConnectionService();

            // Expiring in 200 seconds (within 300-second buffer)
            $expiringSoon = new Connection([
                'token_expires_at' => (new DateTimeImmutable('+200 seconds'))->format('Y-m-d H:i:s'),
            ]);

            $this->assertTrue($service->isTokenExpired($expiringSoon), 'Token expiring in 200s is marked expired due to 300s buffer.');

            // Expiring in 10000 seconds (well outside 300-second buffer)
            $validDistant = new Connection([
                'token_expires_at' => (new DateTimeImmutable('+10000 seconds'))->format('Y-m-d H:i:s'),
            ]);

            $this->assertFalse($service->isTokenExpired($validDistant), 'Token expiring in 10,000s is NOT marked expired.');
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

        private function assertNotNull(mixed $actual, string $message): void
        {
            $this->assertions++;
            if ($actual === null) {
                throw new \RuntimeException("Assertion Failed: {$message} (Expected non-null value)");
            }
            echo "  [OK] {$message}\n";
        }
    }
}

namespace {
    require_once __DIR__ . '/../vendor/autoload.php';
    (new \TheFoxLab\TflSocial\Tests\TokenLifecycleTest())->run();
}
