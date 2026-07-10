<?php

declare(strict_types=1);

namespace TheFoxLab\TflSocial;

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
use TheFoxLab\TflSocial\Providers\Instagram\OAuth as InstagramOAuth;
use TheFoxLab\TflSocial\Providers\Instagram\OAuthResponse as InstagramOAuthResponse;
use TheFoxLab\TflSocial\Services\ConnectionService;

use function iterator_to_array;
use function strtolower;
use function trim;

final class Connector implements ConnectorInterface
{
    private ?string $provider = null;

    private ?FacebookOAuthResponse $facebookOAuthResponse = null;

    private ?Connection $currentConnection = null;

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

    private function assertFacebookProvider(): void
    {
        if ($this->provider !== 'facebook') {
            throw new InvalidArgumentException('Facebook page discovery requires the facebook provider.');
        }
    }
}
