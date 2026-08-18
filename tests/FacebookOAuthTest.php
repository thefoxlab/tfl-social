<?php

declare(strict_types=1);

namespace TheFoxLab\TflSocial\Tests;

use TheFoxLab\TflSocial\Config\TflSocial;
use TheFoxLab\TflSocial\Http\ClientInterface;
use TheFoxLab\TflSocial\Http\Response;
use TheFoxLab\TflSocial\Providers\Facebook\OAuth as FacebookOAuth;
use TheFoxLab\TflSocial\Providers\Facebook\OAuthException;

final class FacebookOAuthTest
{
    private int $assertions = 0;

    public function run(): void
    {
        echo "Running FacebookOAuth Tests...\n\n";

        $this->testAuthorizationUrlGenerationAndStateValidation();
        $this->testExchangeCodeForShortLivedToken();
        $this->testExchangeShortLivedTokenForLongLivedToken();
        $this->testInvalidOAuthResponseThrowsException();
        $this->testRetrieveTokenExpiryAndGrantedScopes();

        echo "\nAll {$this->assertions} FacebookOAuth assertions passed successfully!\n";
    }

    private function testAuthorizationUrlGenerationAndStateValidation(): void
    {
        $config = new TflSocial();
        $config->providers['facebook']['appId'] = '123456789';
        $config->providers['facebook']['appSecret'] = 'secret987';
        $config->providers['facebook']['redirectUri'] = 'http://example.com/callback';

        $oauth = new FacebookOAuth($config);

        $state = $oauth->generateState();
        $this->assertEquals(64, strlen($state), 'Generated state is 64 hex characters (32 bytes).');

        $url = $oauth->authorizationUrl($state);
        $this->assertTrue(str_contains($url, 'client_id=123456789'), 'Auth URL contains client_id.');
        $this->assertTrue(str_contains($url, 'state=' . $state), 'Auth URL contains state.');
        $this->assertTrue(str_contains($url, 'response_type=code'), 'Auth URL contains response_type=code.');

        // Validate state
        $oauth->validateCallbackState($state, $state);
        $this->assertTrue(true, 'State validation succeeded for matching states.');

        $caught = false;
        try {
            $oauth->validateCallbackState($state, 'mismatched_state');
        } catch (OAuthException $e) {
            $caught = true;
        }
        $this->assertTrue($caught, 'State validation throws OAuthException for mismatched state.');
    }

    private function testExchangeCodeForShortLivedToken(): void
    {
        $mockClient = new class implements ClientInterface {
            public function get(string $uri, array $options = []): Response
            {
                return new Response(200, json_encode([
                    'access_token' => 'EAAG_short_lived_token',
                    'token_type' => 'bearer',
                    'expires_in' => 3600,
                ]) ?: '{}', []);
            }
            public function post(string $uri, array $options = []): Response { return new Response(200, '{}', []); }
            public function put(string $uri, array $options = []): Response { return new Response(200, '{}', []); }
            public function patch(string $uri, array $options = []): Response { return new Response(200, '{}', []); }
            public function delete(string $uri, array $options = []): Response { return new Response(200, '{}', []); }
        };

        $config = new TflSocial();
        $config->providers['facebook']['appId'] = '123456789';
        $config->providers['facebook']['appSecret'] = 'secret987';
        $config->providers['facebook']['redirectUri'] = 'http://example.com/callback';

        $oauth = new FacebookOAuth($config, $mockClient);
        $res = $oauth->exchangeCodeForShortLivedToken('auth_code_abc');

        $this->assertEquals('EAAG_short_lived_token', $res->accessToken(), 'Short lived token matches.');
        $this->assertEquals(3600, $res->expiresIn(), 'Expires in 3600 seconds.');
    }

    private function testExchangeShortLivedTokenForLongLivedToken(): void
    {
        $mockClient = new class implements ClientInterface {
            public function get(string $uri, array $options = []): Response
            {
                return new Response(200, json_encode([
                    'access_token' => 'EAAG_long_lived_token',
                    'token_type' => 'bearer',
                    'expires_in' => 5184000,
                ]) ?: '{}', []);
            }
            public function post(string $uri, array $options = []): Response { return new Response(200, '{}', []); }
            public function put(string $uri, array $options = []): Response { return new Response(200, '{}', []); }
            public function patch(string $uri, array $options = []): Response { return new Response(200, '{}', []); }
            public function delete(string $uri, array $options = []): Response { return new Response(200, '{}', []); }
        };

        $config = new TflSocial();
        $config->providers['facebook']['appId'] = '123456789';
        $config->providers['facebook']['appSecret'] = 'secret987';

        $oauth = new FacebookOAuth($config, $mockClient);
        $res = $oauth->exchangeShortLivedTokenForLongLivedToken('EAAG_short_lived_token');

        $this->assertEquals('EAAG_long_lived_token', $res->accessToken(), 'Long-lived token matches.');
        $this->assertEquals(5184000, $res->expiresIn(), 'Expires in 60 days.');
    }

    private function testInvalidOAuthResponseThrowsException(): void
    {
        $mockClient = new class implements ClientInterface {
            public function get(string $uri, array $options = []): Response
            {
                return new Response(200, '{"error":"invalid_code"}', []);
            }
            public function post(string $uri, array $options = []): Response { return new Response(200, '{}', []); }
            public function put(string $uri, array $options = []): Response { return new Response(200, '{}', []); }
            public function patch(string $uri, array $options = []): Response { return new Response(200, '{}', []); }
            public function delete(string $uri, array $options = []): Response { return new Response(200, '{}', []); }
        };

        $config = new TflSocial();
        $config->providers['facebook']['appId'] = '123456789';
        $config->providers['facebook']['appSecret'] = 'secret987';

        $oauth = new FacebookOAuth($config, $mockClient);

        $caught = false;
        try {
            $oauth->exchangeAuthorizationCode('bad_code');
        } catch (OAuthException $e) {
            $caught = true;
            $this->assertTrue(str_contains($e->getMessage(), 'does not contain an access token'), 'Exception message identifies missing token.');
        }
        $this->assertTrue($caught, 'Missing access token in OAuth response throws OAuthException.');
    }

    private function testRetrieveTokenExpiryAndGrantedScopes(): void
    {
        $mockClient = new class implements ClientInterface {
            public function get(string $uri, array $options = []): Response
            {
                if (str_contains($uri, 'debug_token')) {
                    return new Response(200, json_encode([
                        'data' => [
                            'expires_at' => 1800000000,
                            'is_valid' => true,
                        ],
                    ]) ?: '{}', []);
                }

                if (str_contains($uri, 'permissions')) {
                    return new Response(200, json_encode([
                        'data' => [
                            ['permission' => 'pages_read_engagement', 'status' => 'granted'],
                            ['permission' => 'pages_show_list', 'status' => 'granted'],
                            ['permission' => 'publish_posts', 'status' => 'declined'],
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

        $config = new TflSocial();
        $config->providers['facebook']['appId'] = '123456789';
        $config->providers['facebook']['appSecret'] = 'secret987';

        $oauth = new FacebookOAuth($config, $mockClient);

        $expiry = $oauth->retrieveTokenExpiry('EAAG_sample_token');
        $this->assertNotNull($expiry->expiresAt(), 'retrieveTokenExpiry returns expiresAt DateTime.');
        $this->assertEquals(1800000000, $expiry->expiresAt()?->getTimestamp(), 'Expiry timestamp matches.');

        $scopes = $oauth->retrieveGrantedScopes('EAAG_sample_token');
        $this->assertEquals(['pages_read_engagement', 'pages_show_list'], $scopes->scopes(), 'Granted scopes filtered correctly (declined excluded).');
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

    private function assertNotNull(mixed $actual, string $message): void
    {
        $this->assertions++;
        if ($actual === null) {
            throw new \RuntimeException("Assertion Failed: {$message} (Expected non-null value)");
        }
        echo "  [OK] {$message}\n";
    }
}

if (basename(__FILE__) === basename($_SERVER['PHP_SELF'] ?? '')) {
    require_once __DIR__ . '/TokenLifecycleTest.php';
    (new FacebookOAuthTest())->run();
}
