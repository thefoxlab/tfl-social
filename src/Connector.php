<?php

declare(strict_types=1);

namespace TheFoxLab\TflSocial;

use DateTimeInterface;
use InvalidArgumentException;
use TheFoxLab\TflSocial\Config\TflSocial;
use TheFoxLab\TflSocial\Contracts\ConnectorInterface;
use TheFoxLab\TflSocial\Entities\Connection;
use TheFoxLab\TflSocial\Http\Client;
use TheFoxLab\TflSocial\Http\ClientInterface;
use TheFoxLab\TflSocial\Providers\Facebook\OAuth as FacebookOAuth;
use TheFoxLab\TflSocial\Providers\Facebook\OAuthResponse as FacebookOAuthResponse;
use TheFoxLab\TflSocial\Providers\Facebook\Page;
use TheFoxLab\TflSocial\Providers\Facebook\PageCollection;
use TheFoxLab\TflSocial\Providers\Facebook\PageService;
use TheFoxLab\TflSocial\Providers\Instagram\BusinessAccount;
use TheFoxLab\TflSocial\Providers\Instagram\BusinessAccountCollection;
use TheFoxLab\TflSocial\Providers\Instagram\BusinessAccountService;
use TheFoxLab\TflSocial\Providers\Instagram\OAuth as InstagramOAuth;
use TheFoxLab\TflSocial\Providers\Instagram\OAuthResponse as InstagramOAuthResponse;
use TheFoxLab\TflSocial\Services\ConnectionService;

use function iterator_to_array;
use function is_string;
use function strtolower;
use function trim;

final class Connector implements ConnectorInterface
{
    private ?string $provider = null;

    private ?FacebookOAuthResponse $facebookOAuthResponse = null;

    private ?Connection $currentConnection = null;

    private ?Connection $currentInstagramConnection = null;

    public function __construct(
        private ?TflSocial $config = null,
        private ?ClientInterface $client = null,
        private ?ConnectionService $connectionService = null
    ) {
    }

    public function provider(string $provider): self
    {
        $provider = strtolower(trim($provider));

        if ($provider === '') {
            throw new InvalidArgumentException('Provider name cannot be empty.');
        }

        $this->provider = $provider;
        $this->facebookOAuthResponse = null;
        $this->currentConnection = null;
        $this->currentInstagramConnection = null;

        return $this;
    }

    public function accessToken(string $token): self
    {
        $token = trim($token);

        if ($token === '') {
            throw new InvalidArgumentException('Access token cannot be empty.');
        }

        $this->assertFacebookProvider();
        $this->facebookOAuthResponse = new FacebookOAuthResponse(accessToken: $token);

        return $this;
    }

    public function authorizationUrl(?string $state = null): string
    {
        return $this->oauth()->authorizationUrl($state);
    }

    public function generateState(): string
    {
        return $this->oauth()->generateState();
    }

    public function validateCallbackState(string $expectedState, string $actualState): void
    {
        $this->oauth()->validateCallbackState($expectedState, $actualState);
    }

    public function exchangeAuthorizationCode(string $code): FacebookOAuthResponse|InstagramOAuthResponse
    {
        return $this->storeOAuthResponse($this->oauth()->exchangeAuthorizationCode($code));
    }

    public function exchangeCodeForShortLivedToken(string $code): FacebookOAuthResponse|InstagramOAuthResponse
    {
        return $this->storeOAuthResponse($this->oauth()->exchangeCodeForShortLivedToken($code));
    }

    public function exchangeShortLivedTokenForLongLivedToken(string $accessToken): FacebookOAuthResponse|InstagramOAuthResponse
    {
        return $this->storeOAuthResponse($this->oauth()->exchangeShortLivedTokenForLongLivedToken($accessToken));
    }

    public function retrieveTokenExpiry(string $accessToken): FacebookOAuthResponse|InstagramOAuthResponse
    {
        return $this->oauth()->retrieveTokenExpiry($accessToken);
    }

    public function retrieveGrantedScopes(string $accessToken): FacebookOAuthResponse|InstagramOAuthResponse
    {
        return $this->oauth()->retrieveGrantedScopes($accessToken);
    }

    public function pages(): PageCollection
    {
        $this->assertFacebookProvider();

        return $this->pageService()->pages($this->facebookOAuthResponse());
    }

    public function page(string $pageId): Page
    {
        $this->assertFacebookProvider();

        return $this->pageService()->page($pageId, $this->facebookOAuthResponse());
    }

    public function connectPage(string $pageId): Connection
    {
        $this->assertFacebookProvider();

        $page = $this->page($pageId);
        $this->currentConnection = $this->connectionService()->connectProvider(
            accountId: null,
            provider: 'facebook',
            externalId: $page->pageId(),
            externalName: $page->name(),
            metadata: [
                'category' => $page->category(),
                'picture' => $page->picture(),
            ],
            accessToken: $page->accessToken(),
            tokenExpiresAt: $this->expiresAt($this->facebookOAuthResponse()),
            permissions: iterator_to_array($page->tasks(), false)
        );

        return $this->currentConnection;
    }

    public function disconnectPage(): Connection
    {
        $this->assertFacebookProvider();

        $connection = $this->currentConnection();

        if ($connection === null) {
            throw new InvalidArgumentException('No current Facebook page connection is available.');
        }

        $this->currentConnection = $this->connectionService()->disconnectProvider($connection->social_connection_id);

        return $this->currentConnection;
    }

    public function instagramBusinesses(): BusinessAccountCollection
    {
        $this->assertFacebookProvider();

        return $this->instagramBusinessService()->businessAccounts($this->activePageConnection());
    }

    public function instagramBusiness(string $accountId): BusinessAccount
    {
        $account = $this->instagramBusinesses()->find($accountId);

        if ($account === null) {
            throw new InvalidArgumentException(sprintf('Instagram Business account [%s] was not found.', $accountId));
        }

        return $account;
    }

    public function connectInstagramBusiness(string $accountId): Connection
    {
        $account = $this->instagramBusiness($accountId);
        $pageConnection = $this->activePageConnection();
        $this->currentInstagramConnection = $this->connectionService()->connectProvider(
            accountId: $pageConnection->social_account_id,
            provider: 'instagram',
            externalId: $account->accountId(),
            externalName: $account->username() ?? $account->name(),
            metadata: [
                'name' => $account->name(),
                'profile_picture' => $account->profilePicture(),
            ],
            accessToken: $this->stringProperty($pageConnection->access_token, 'Facebook Page access token is missing.'),
            tokenExpiresAt: is_string($pageConnection->token_expires_at) ? $pageConnection->token_expires_at : null,
            parentConnectionId: $this->connectionId($pageConnection)
        );

        return $this->currentInstagramConnection;
    }

    public function disconnectInstagramBusiness(): Connection
    {
        if ($this->currentInstagramConnection === null) {
            throw new InvalidArgumentException('No current Instagram Business connection is available.');
        }

        $this->currentInstagramConnection = $this->connectionService()->disconnectProvider(
            $this->connectionId($this->currentInstagramConnection)
        );

        return $this->currentInstagramConnection;
    }

    public function currentInstagramConnection(): ?Connection
    {
        if ($this->currentInstagramConnection === null) {
            return null;
        }

        $this->currentInstagramConnection = $this->connectionService()->getConnection(
            $this->connectionId($this->currentInstagramConnection)
        );

        return $this->currentInstagramConnection;
    }

    public function connectionStatus(): string
    {
        $connection = $this->currentConnection();

        if ($connection === null) {
            return 'disconnected';
        }

        if ($this->connectionService()->isTokenExpired($connection)) {
            $this->currentConnection = $this->connectionService()->updateStatus($this->connectionId($connection), 'expired');

            return 'expired';
        }

        if (! is_string($connection->status)) {
            return 'unknown';
        }

        return $connection->status;
    }

    public function tokenExpired(): bool
    {
        $connection = $this->currentConnection();

        return $connection !== null && $this->connectionService()->isTokenExpired($connection);
    }

    public function refreshToken(): Connection
    {
        $connection = $this->requiredPageConnection();
        $accessToken = $this->stringProperty($connection->access_token, 'Facebook Page access token is missing.');
        $response = $this->facebookOAuth()->exchangeShortLivedTokenForLongLivedToken($accessToken);
        $this->currentConnection = $this->connectionService()->updateTokens(
            connectionId: $this->connectionId($connection),
            accessToken: $this->stringProperty($response->accessToken(), 'Facebook token refresh did not return a token.'),
            tokenExpiresAt: $this->expiresAt($response)
        );

        foreach ($this->connectionService()->childConnections($this->connectionId($this->currentConnection)) as $child) {
            if ($child->provider === 'instagram') {
                $this->currentInstagramConnection = $this->connectionService()->updateTokens(
                    connectionId: $this->connectionId($child),
                    accessToken: $this->stringProperty($this->currentConnection->access_token, 'Facebook token refresh failed.'),
                    tokenExpiresAt: is_string($this->currentConnection->token_expires_at)
                        ? $this->currentConnection->token_expires_at
                        : null
                );
            }
        }

        return $this->currentConnection;
    }

    public function reconnect(): Connection
    {
        $connection = $this->requiredPageConnection();

        if ($this->connectionService()->isTokenExpired($connection)) {
            $connection = $this->refreshToken();
        }

        $this->currentConnection = $this->connectionService()->updateStatus($this->connectionId($connection), 'active');

        return $this->currentConnection;
    }

    public function currentConnection(): ?Connection
    {
        if ($this->currentConnection === null) {
            return null;
        }

        $connectionId = $this->currentConnection->social_connection_id;

        if (! is_int($connectionId) && ! is_string($connectionId)) {
            return $this->currentConnection;
        }

        $this->currentConnection = $this->connectionService()->getConnection($connectionId);

        return $this->currentConnection;
    }

    private function storeOAuthResponse(FacebookOAuthResponse|InstagramOAuthResponse $response): FacebookOAuthResponse|InstagramOAuthResponse
    {
        if ($response instanceof FacebookOAuthResponse) {
            $this->facebookOAuthResponse = $response;
        }

        return $response;
    }

    private function oauth(): FacebookOAuth|InstagramOAuth
    {
        return match ($this->provider) {
            'facebook' => new FacebookOAuth($this->config(), $this->client()),
            'instagram' => new InstagramOAuth($this->config(), $this->client()),
            null => throw new InvalidArgumentException('Provider must be selected before OAuth operations.'),
            default => throw new InvalidArgumentException(sprintf(
                'Provider [%s] does not support OAuth.',
                $this->provider
            )),
        };
    }

    private function config(): TflSocial
    {
        return $this->config ??= new TflSocial();
    }

    private function client(): ClientInterface
    {
        return $this->client ??= new Client($this->config());
    }

    private function pageService(): PageService
    {
        return new PageService($this->config(), $this->client());
    }

    private function instagramBusinessService(): BusinessAccountService
    {
        return new BusinessAccountService($this->config(), $this->client());
    }

    private function connectionService(): ConnectionService
    {
        return $this->connectionService ??= new ConnectionService();
    }

    private function facebookOAuthResponse(): FacebookOAuthResponse
    {
        if ($this->facebookOAuthResponse === null) {
            throw new InvalidArgumentException('A successful Facebook OAuth response is required before page discovery.');
        }

        return $this->facebookOAuthResponse;
    }

    private function activePageConnection(): Connection
    {
        $connection = $this->requiredPageConnection();

        if ($this->connectionService()->isTokenExpired($connection)) {
            return $this->refreshToken();
        }

        return $connection;
    }

    private function requiredPageConnection(): Connection
    {
        $connection = $this->currentConnection();

        if ($connection === null) {
            throw new InvalidArgumentException('A connected Facebook Page is required.');
        }

        return $connection;
    }

    private function facebookOAuth(): FacebookOAuth
    {
        return new FacebookOAuth($this->config(), $this->client());
    }

    private function expiresAt(FacebookOAuthResponse $response): ?string
    {
        return $this->formatDateTime($response->expiresAt());
    }

    private function formatDateTime(?DateTimeInterface $dateTime): ?string
    {
        return $dateTime?->format('Y-m-d H:i:s');
    }

    private function connectionId(Connection $connection): int|string
    {
        $connectionId = $connection->social_connection_id;

        if (! is_int($connectionId) && ! is_string($connectionId)) {
            throw new InvalidArgumentException('Connection id is missing.');
        }

        return $connectionId;
    }

    private function stringProperty(mixed $value, string $message): string
    {
        if (! is_string($value) || trim($value) === '') {
            throw new InvalidArgumentException($message);
        }

        return $value;
    }

    private function assertFacebookProvider(): void
    {
        if ($this->provider !== 'facebook') {
            throw new InvalidArgumentException('Facebook page discovery requires the facebook provider.');
        }
    }
}
