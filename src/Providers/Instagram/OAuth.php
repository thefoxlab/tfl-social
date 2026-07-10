<?php

declare(strict_types=1);

namespace TheFoxLab\TflSocial\Providers\Instagram;

use TheFoxLab\TflSocial\Config\TflSocial;
use TheFoxLab\TflSocial\Http\ClientInterface;
use TheFoxLab\TflSocial\Http\Factory;

use function bin2hex;
use function hash_equals;
use function http_build_query;
use function implode;
use function is_array;
use function is_string;
use function random_bytes;
use function sprintf;
use function trim;
use Throwable;

final class OAuth
{
    private const AUTHORIZE_BASE_URL = 'https://www.facebook.com';

    public function __construct(
        private readonly TflSocial $config = new TflSocial(),
        ?ClientInterface $client = null
    ) {
        $this->client = $client ?? Factory::create($this->config);
    }

    private readonly ClientInterface $client;

    public function authorizationUrl(?string $state = null): string
    {
        $state ??= $this->generateState();

        return sprintf(
            '%s/%s/dialog/oauth?%s',
            self::AUTHORIZE_BASE_URL,
            $this->graphVersion(),
            http_build_query([
                'client_id' => $this->appId(),
                'redirect_uri' => $this->redirectUri(),
                'state' => $state,
                'response_type' => 'code',
                'scope' => implode(',', $this->permissions()),
            ])
        );
    }

    public function generateState(): string
    {
        try {
            return bin2hex(random_bytes(32));
        } catch (Throwable) {
            throw OAuthException::configuration('Unable to generate OAuth state.');
        }
    }

    public function validateCallbackState(string $expectedState, string $actualState): void
    {
        if ($expectedState === '' || $actualState === '' || ! hash_equals($expectedState, $actualState)) {
            throw OAuthException::invalidState();
        }
    }

    public function exchangeAuthorizationCode(string $code): OAuthResponse
    {
        return $this->exchangeCodeForShortLivedToken($code);
    }

    public function exchangeCodeForShortLivedToken(string $code): OAuthResponse
    {
        $this->assertNotEmpty($code, 'Authorization code is required.');

        throw OAuthException::notImplemented();
    }

    public function exchangeShortLivedTokenForLongLivedToken(string $accessToken): OAuthResponse
    {
        $this->assertNotEmpty($accessToken, 'Short-lived access token is required.');

        throw OAuthException::notImplemented();
    }

    public function retrieveTokenExpiry(string $accessToken): OAuthResponse
    {
        $this->assertNotEmpty($accessToken, 'Access token is required.');

        throw OAuthException::notImplemented();
    }

    public function retrieveGrantedScopes(string $accessToken): OAuthResponse
    {
        $this->assertNotEmpty($accessToken, 'Access token is required.');

        throw OAuthException::notImplemented();
    }

    private function appId(): string
    {
        return $this->providerValue('appId');
    }

    private function redirectUri(): string
    {
        return $this->providerValue('redirectUri');
    }

    private function graphVersion(): string
    {
        $version = trim($this->config->graphVersion);

        if ($version === '') {
            throw OAuthException::configuration('Meta Graph version is not configured.');
        }

        return $version;
    }

    /**
     * @return list<string>
     */
    private function permissions(): array
    {
        $permissions = $this->config->providers['instagram']['permissions'] ?? [];

        if (! is_array($permissions)) {
            return [];
        }

        $values = [];

        foreach ($permissions as $permission) {
            if (is_string($permission) && $permission !== '') {
                $values[] = $permission;
            }
        }

        return $values;
    }

    private function providerValue(string $key): string
    {
        $value = $this->config->providers['instagram'][$key] ?? null;

        if (! is_string($value) || trim($value) === '') {
            throw OAuthException::configuration(sprintf('Instagram OAuth [%s] is not configured.', $key));
        }

        return $value;
    }

    private function assertNotEmpty(string $value, string $message): void
    {
        if (trim($value) === '') {
            throw OAuthException::configuration($message);
        }
    }
}
