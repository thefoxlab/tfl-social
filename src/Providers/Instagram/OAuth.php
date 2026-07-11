<?php

declare(strict_types=1);

namespace TheFoxLab\TflSocial\Providers\Instagram;

use DateTimeImmutable;
use JsonException;
use TheFoxLab\TflSocial\Config\TflSocial;
use TheFoxLab\TflSocial\Http\Client;
use TheFoxLab\TflSocial\Http\ClientInterface;
use TheFoxLab\TflSocial\Http\HttpException;
use TheFoxLab\TflSocial\Http\Response;

use function bin2hex;
use function ctype_digit;
use function hash_equals;
use function http_build_query;
use function implode;
use function is_array;
use function is_int;
use function is_string;
use function random_bytes;
use function sprintf;
use function time;
use function trim;
use Throwable;

final class OAuth
{
    private const AUTHORIZE_BASE_URL = 'https://www.facebook.com';

    private const INSTAGRAM_GRAPH_BASE_URL = 'https://graph.instagram.com';

    public function __construct(
        private readonly TflSocial $config = new TflSocial(),
        ?ClientInterface $client = null
    ) {
        $this->client = $client ?? new Client($this->config);
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

        return $this->tokenResponse($this->get('/access_token', [
            'grant_type' => 'ig_exchange_token',
            'client_secret' => $this->appSecret(),
            'access_token' => $accessToken,
        ]));
    }

    public function retrieveTokenExpiry(string $accessToken): OAuthResponse
    {
        $this->assertNotEmpty($accessToken, 'Access token is required.');

        return $this->tokenResponse($this->get('/refresh_access_token', [
            'grant_type' => 'ig_refresh_token',
            'access_token' => $accessToken,
        ]));
    }

    public function retrieveGrantedScopes(string $accessToken): OAuthResponse
    {
        $this->assertNotEmpty($accessToken, 'Access token is required.');

        return new OAuthResponse(accessToken: $accessToken);
    }

    /**
     * @param array<string, string> $query
     */
    private function get(string $uri, array $query): Response
    {
        try {
            $response = $this->client->get($uri, [
                'base_url' => self::INSTAGRAM_GRAPH_BASE_URL,
                'query' => $query,
            ]);
        } catch (HttpException $exception) {
            throw OAuthException::requestFailed($exception->getMessage(), $exception);
        }

        if (! $response->successful()) {
            throw OAuthException::requestFailed(sprintf(
                'Instagram OAuth request failed with status code [%d].',
                $response->statusCode()
            ));
        }

        return $response;
    }

    private function tokenResponse(Response $response): OAuthResponse
    {
        $payload = $this->json($response);
        $accessToken = $payload['access_token'] ?? null;

        if (! is_string($accessToken) || $accessToken === '') {
            throw OAuthException::invalidResponse('OAuth response does not contain an access token.');
        }

        $expiresIn = $this->integerOrNull($payload['expires_in'] ?? null);
        $expiresAt = $expiresIn === null
            ? null
            : (new DateTimeImmutable())->setTimestamp(time() + $expiresIn);

        return new OAuthResponse(
            accessToken: $accessToken,
            tokenType: is_string($payload['token_type'] ?? null) ? $payload['token_type'] : null,
            expiresIn: $expiresIn,
            expiresAt: $expiresAt
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function json(Response $response): array
    {
        try {
            return $response->json();
        } catch (JsonException $exception) {
            throw OAuthException::invalidResponse($exception->getMessage());
        }
    }

    private function integerOrNull(mixed $value): ?int
    {
        if (is_int($value)) {
            return $value;
        }

        if (is_string($value) && ctype_digit($value)) {
            return (int) $value;
        }

        return null;
    }

    private function appId(): string
    {
        return $this->providerValue('appId');
    }

    private function appSecret(): string
    {
        return $this->providerValue('appSecret');
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
