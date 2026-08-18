<?php

declare(strict_types=1);

namespace CodeIgniter\Config {
    if (! class_exists(BaseService::class)) {
        class BaseService
        {
            protected static array $instances = [];

            public static function getSharedInstance(string $key, ...$params): mixed
            {
                return static::$instances[$key] ??= static::$key(...array_merge($params, [false]));
            }
        }
    }
}

namespace TheFoxLab\TflSocial\Tests {

    use TheFoxLab\TflSocial\Config\Services;
    use TheFoxLab\TflSocial\Config\TflSocial as TflSocialConfig;
    use TheFoxLab\TflSocial\Connector;
    use TheFoxLab\TflSocial\Providers\Facebook\OAuth as FacebookOAuth;
    use TheFoxLab\TflSocial\TflSocial;

class CustomAppConfig extends TflSocialConfig
{
    public array $providers = [
        'facebook' => [
            'enabled' => true,
            'appId' => 'custom-app-id-999',
            'appSecret' => 'custom-app-secret-999',
            'redirectUri' => 'setup/callback',
            'permissions' => ['pages_show_list', 'instagram_basic'],
        ],
    ];
}

final class ConfigResolutionTest
{
    private int $assertions = 0;

    public function run(): void
    {
        echo "Running Config Resolution Tests...\n\n";

        $this->testExplicitConfigPreservation();
        $this->testFallbackToPackageDefault();
        $this->testConfigPropagationThroughOAuthChain();
        $this->testServicesTflSocialFactory();

        echo "\nAll {$this->assertions} Config Resolution assertions passed successfully!\n";
    }

    private function testExplicitConfigPreservation(): void
    {
        $customConfig = new CustomAppConfig();
        $resolved = TflSocialConfig::resolve($customConfig);

        $this->assertSame($customConfig, $resolved, 'Explicit configuration instance is preserved without alteration.');
    }

    private function testFallbackToPackageDefault(): void
    {
        $resolved = TflSocialConfig::resolve(null);

        $this->assertInstanceOf(TflSocialConfig::class, $resolved, 'Resolves to instance of TflSocialConfig.');
        $this->assertSame('social/callback/facebook', $resolved->providers['facebook']['redirectUri'], 'Default redirectUri is preserved.');
    }

    private function testConfigPropagationThroughOAuthChain(): void
    {
        $customConfig = new CustomAppConfig();

        // 1. Through Connector directly
        $connector = new Connector(config: $customConfig);
        $connector->provider('facebook');
        $authUrlFromConnector = $connector->authorizationUrl();

        $this->assertTrue(str_contains($authUrlFromConnector, 'client_id=custom-app-id-999'), 'Connector auth URL uses custom appId.');
        $this->assertTrue(str_contains($authUrlFromConnector, urlencode('setup/callback')) || str_contains($authUrlFromConnector, 'setup%2Fcallback'), 'Connector auth URL contains custom redirectUri.');

        // 2. Through TflSocial facade
        $social = new TflSocial(config: $customConfig);
        $authUrlFromSocial = $social->facebook()->authorizationUrl();

        $this->assertTrue(str_contains($authUrlFromSocial, 'client_id=custom-app-id-999'), 'TflSocial facade auth URL uses custom appId.');
        $this->assertTrue(str_contains($authUrlFromSocial, urlencode('setup/callback')) || str_contains($authUrlFromSocial, 'setup%2Fcallback'), 'TflSocial facade auth URL contains custom redirectUri.');

        // 3. Through FacebookOAuth directly
        $oauth = new FacebookOAuth(config: $customConfig);
        $authUrlFromOAuth = $oauth->authorizationUrl();

        $this->assertTrue(str_contains($authUrlFromOAuth, 'client_id=custom-app-id-999'), 'FacebookOAuth directly uses custom appId.');
        $this->assertTrue(str_contains($authUrlFromOAuth, urlencode('setup/callback')) || str_contains($authUrlFromOAuth, 'setup%2Fcallback'), 'FacebookOAuth directly uses custom redirectUri.');
    }

    private function testServicesTflSocialFactory(): void
    {
        $customConfig = new CustomAppConfig();
        $social = Services::tflSocial($customConfig, false);

        $authUrl = $social->facebook()->authorizationUrl();
        $this->assertTrue(str_contains($authUrl, 'client_id=custom-app-id-999'), 'Services::tflSocial() respects provided configuration.');
    }

    private function assertTrue(bool $condition, string $message): void
    {
        $this->assertions++;
        if (! $condition) {
            echo "  [FAIL] {$message}\n";
            exit(1);
        }
        echo "  [OK] {$message}\n";
    }

    private function assertSame(mixed $expected, mixed $actual, string $message): void
    {
        $this->assertions++;
        if ($expected !== $actual) {
            echo "  [FAIL] {$message} Expected: " . var_export($expected, true) . ", got: " . var_export($actual, true) . "\n";
            exit(1);
        }
        echo "  [OK] {$message}\n";
    }

    private function assertInstanceOf(string $expectedClass, object $actual, string $message): void
    {
        $this->assertions++;
        if (! ($actual instanceof $expectedClass)) {
            echo "  [FAIL] {$message} Expected instance of {$expectedClass}, got: " . get_class($actual) . "\n";
            exit(1);
        }
        echo "  [OK] {$message}\n";
    }
}
}

