<?php

declare(strict_types=1);

namespace TheFoxLab\TflSocial;

use DateTimeInterface;
use InvalidArgumentException;
use TheFoxLab\TflSocial\Config\TflSocial;
use TheFoxLab\TflSocial\Contracts\ConnectorInterface;
use TheFoxLab\TflSocial\Entities\Account;
use TheFoxLab\TflSocial\Entities\Connection;
use TheFoxLab\TflSocial\Http\Client;
use TheFoxLab\TflSocial\Http\ClientInterface;
use TheFoxLab\TflSocial\Providers\Facebook\OAuth as FacebookOAuth;
use TheFoxLab\TflSocial\Providers\Facebook\OAuthResponse as FacebookOAuthResponse;
use TheFoxLab\TflSocial\Providers\Facebook\Page;
use TheFoxLab\TflSocial\Providers\Facebook\PageCollection;
use TheFoxLab\TflSocial\Providers\Facebook\PageService;
use TheFoxLab\TflSocial\Providers\Facebook\GraphService as FacebookGraphService;
use TheFoxLab\TflSocial\Providers\Instagram\BusinessAccount;
use TheFoxLab\TflSocial\Providers\Instagram\BusinessAccountCollection;
use TheFoxLab\TflSocial\Providers\Instagram\BusinessAccountService;
use TheFoxLab\TflSocial\Providers\Instagram\GraphService as InstagramGraphService;
use TheFoxLab\TflSocial\Providers\Meta\FeatureUnavailableResponse;
use TheFoxLab\TflSocial\Providers\Meta\GraphCollection;
use TheFoxLab\TflSocial\Providers\Meta\GraphRequestOptions;
use TheFoxLab\TflSocial\Providers\Meta\GraphResponse;
use TheFoxLab\TflSocial\Providers\Meta\Pagination;
use TheFoxLab\TflSocial\Services\AccountService;
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

    private ?Account $currentAccount = null;

    public function __construct(
        private ?TflSocial $config = null,
        private ?ClientInterface $client = null,
        private ?ConnectionService $connectionService = null,
        private ?AccountService $accountService = null
    ) {
    }

    public function account(Account $account): self
    {
        if ($this->currentAccount !== $account) {
            $this->currentConnection = null;
            $this->currentInstagramConnection = null;
        }

        $this->currentAccount = $account;

        return $this;
    }

    public function provider(string $provider): self
    {
        $provider = strtolower(trim($provider));

        if ($provider === '') {
            throw new InvalidArgumentException('Provider name cannot be empty.');
        }

        if ($provider !== 'facebook') {
            throw new InvalidArgumentException(sprintf(
                'Provider [%s] is not supported. Instagram is accessed through the connected Facebook Page token.',
                $provider
            ));
        }

        if ($this->provider !== $provider) {
            $this->facebookOAuthResponse = null;
            $this->currentConnection = null;
            $this->currentInstagramConnection = null;
        }

        $this->provider = $provider;

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

    public function exchangeAuthorizationCode(string $code): FacebookOAuthResponse
    {
        return $this->storeOAuthResponse($this->oauth()->exchangeAuthorizationCode($code));
    }

    public function exchangeCodeForShortLivedToken(string $code): FacebookOAuthResponse
    {
        return $this->storeOAuthResponse($this->oauth()->exchangeCodeForShortLivedToken($code));
    }

    public function exchangeShortLivedTokenForLongLivedToken(string $accessToken): FacebookOAuthResponse
    {
        return $this->storeOAuthResponse($this->oauth()->exchangeShortLivedTokenForLongLivedToken($accessToken));
    }

    public function retrieveTokenExpiry(string $accessToken): FacebookOAuthResponse
    {
        return $this->oauth()->retrieveTokenExpiry($accessToken);
    }

    public function retrieveGrantedScopes(string $accessToken): FacebookOAuthResponse
    {
        return $this->oauth()->retrieveGrantedScopes($accessToken);
    }

    public function pages(): PageCollection
    {
        $this->assertFacebookProvider();

        return $this->pageService()->pages($this->facebookOAuthResponse());
    }
    
    public function businessPages(): PageCollection
    {
        $this->assertFacebookProvider();
        
        return $this->pageService()->businessPages($this->facebookOAuthResponse());
    }

    /**
     * @param list<string>|string|null $fields
     */
    public function page(?string $pageId = null, array|string|null $fields = null): Page|GraphResponse
    {
        $this->assertFacebookProvider();

        if ($pageId !== null) {
            return $this->pageService()->page($pageId, $this->facebookOAuthResponse());
        }

        return $this->facebookGraph()->node($this->activePageConnection(), $this->options($fields));
    }

    public function connectPage(string $pageId): Connection
    {
        $this->assertFacebookProvider();

        $page = $this->pageService()->page($pageId, $this->facebookOAuthResponse());
        $this->currentConnection = $this->connectionService()->connectProvider(
            accountId: $this->accountId(),
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
            accountId: $this->accountId(),
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
            $this->currentInstagramConnection = $this->connectionService()->currentConnection(
                $this->accountId(),
                'instagram'
            );
        }

        if ($this->currentInstagramConnection === null) {
            $pageConnection = $this->currentConnection();

            if ($pageConnection !== null) {
                foreach ($this->connectionService()->childConnections($this->connectionId($pageConnection)) as $child) {
                    if ($child->provider === 'instagram') {
                        $this->currentInstagramConnection = $child;

                        break;
                    }
                }
            }
        }

        if ($this->currentInstagramConnection === null) {
            return null;
        }

        $this->currentInstagramConnection = $this->connectionService()->getConnection(
            $this->connectionId($this->currentInstagramConnection)
        );

        return $this->currentInstagramConnection;
    }

    /**
     * @param list<string>|string|null $fields
     */
    public function feed(array|string|null $fields = null, ?int $limit = null, ?string $after = null, ?string $before = null): GraphCollection
    {
        return $this->facebookGraph()->edge($this->activePageConnection(), 'feed', $this->options($fields, $limit, $after, $before));
    }

    /**
     * @param list<string>|string|null $fields
     */
    public function posts(array|string|null $fields = null, ?int $limit = null, ?string $after = null, ?string $before = null): GraphCollection
    {
        return $this->facebookGraph()->edge($this->activePageConnection(), 'posts', $this->options($fields, $limit, $after, $before));
    }

    /**
     * @param list<string>|string|null $fields
     */
    public function photos(array|string|null $fields = null, ?int $limit = null, ?string $after = null, ?string $before = null): GraphCollection
    {
        return $this->facebookGraph()->edge($this->activePageConnection(), 'photos', $this->options($fields, $limit, $after, $before));
    }

    /**
     * @param list<string>|string|null $fields
     */
    public function videos(array|string|null $fields = null, ?int $limit = null, ?string $after = null, ?string $before = null): GraphCollection
    {
        return $this->facebookGraph()->edge($this->activePageConnection(), 'videos', $this->options($fields, $limit, $after, $before));
    }

    /**
     * @param list<string>|string|null $fields
     */
    public function albums(array|string|null $fields = null, ?int $limit = null, ?string $after = null, ?string $before = null): GraphCollection
    {
        return $this->facebookGraph()->edge($this->activePageConnection(), 'albums', $this->options($fields, $limit, $after, $before));
    }

    /**
     * @param list<string>|string|null $fields
     */
    public function events(array|string|null $fields = null, ?int $limit = null, ?string $after = null, ?string $before = null): GraphCollection
    {
        return $this->facebookGraph()->edge($this->activePageConnection(), 'events', $this->options($fields, $limit, $after, $before));
    }

    /**
     * @param list<string>|string|null $fields
     */
    public function reviews(array|string|null $fields = null, ?int $limit = null, ?string $after = null, ?string $before = null): GraphCollection
    {
        return $this->facebookGraph()->edge($this->activePageConnection(), 'ratings', $this->options($fields, $limit, $after, $before));
    }

    /**
     * @param list<string>|string|null $fields
     */
    public function profile(array|string|null $fields = null): GraphResponse
    {
        return $this->instagramGraph()->profile($this->activeInstagramConnection(), $this->options($fields));
    }

    /**
     * @param list<string>|string|null $fields
     */
    public function media(array|string|null $fields = null, ?int $limit = null, ?string $after = null, ?string $before = null): GraphCollection
    {
        return $this->instagramGraph()->media($this->activeInstagramConnection(), $this->options($fields, $limit, $after, $before));
    }

    /**
     * @param list<string>|string|null $fields
     */
    public function mediaById(string $mediaId, array|string|null $fields = null): GraphResponse
    {
        return $this->instagramGraph()->mediaById($this->activeInstagramConnection(), $mediaId, $this->options($fields));
    }

    /**
     * @param list<string>|string|null $fields
     */
    public function reels(array|string|null $fields = null, ?int $limit = null, ?string $after = null, ?string $before = null): GraphCollection
    {
        return $this->instagramGraph()->reels($this->activeInstagramConnection(), $this->options($fields, $limit, $after, $before));
    }

    /**
     * @param list<string>|string|null $fields
     */
    public function carousel(array|string|null $fields = null, ?int $limit = null, ?string $after = null, ?string $before = null): GraphCollection
    {
        return $this->instagramGraph()->carousel($this->activeInstagramConnection(), $this->options($fields, $limit, $after, $before));
    }

    /**
     * @param list<string>|string|null $fields
     */
    public function stories(array|string|null $fields = null, ?int $limit = null, ?string $after = null, ?string $before = null): GraphCollection|FeatureUnavailableResponse
    {
        return $this->instagramGraph()->stories($this->activeInstagramConnection(), $this->options($fields, $limit, $after, $before));
    }


    /**
     * @param list<string>|string|null $fields
     */
    public function ownMediaByHashtag(string $hashtag, array|string|null $fields = null, ?int $limit = null, ?string $after = null, ?string $before = null): GraphCollection
    {
        return $this->instagramGraph()->ownMediaByHashtag($this->activeInstagramConnection(), $hashtag, $this->options($fields, $limit, $after, $before));
    }

    public function hashtagSearch(string $hashtag): GraphResponse|FeatureUnavailableResponse
    {
        return $this->instagramGraph()->hashtagSearch($this->activeInstagramConnection(), $hashtag);
    }

    /**
     * @param list<string>|string|null $fields
     */
    public function recentHashtagMedia(string $hashtagId, array|string|null $fields = null, ?int $limit = null, ?string $after = null, ?string $before = null): GraphCollection|FeatureUnavailableResponse
    {
        return $this->instagramGraph()->recentHashtagMedia($this->activeInstagramConnection(), $hashtagId, $this->options($fields, $limit, $after, $before));
    }

    public function pagination(GraphCollection $collection): Pagination
    {
        return $collection->pagination();
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

        $this->currentConnection = $this->connectionService()->updateStatus(
            $this->connectionId($connection),
            Connection::STATUS_ACTIVE
        );

        return $this->currentConnection;
    }

    public function currentConnection(): ?Connection
    {
        $this->assertFacebookProvider();

        return $this->resolveCurrentConnection('facebook');
    }

    private function storeOAuthResponse(FacebookOAuthResponse $response): FacebookOAuthResponse
    {
        $this->facebookOAuthResponse = $response;

        return $response;
    }

    private function oauth(): FacebookOAuth
    {
        return match ($this->provider) {
            'facebook' => new FacebookOAuth($this->config(), $this->client()),
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

    private function facebookGraph(): FacebookGraphService
    {
        return new FacebookGraphService($this->config(), $this->client());
    }

    private function instagramGraph(): InstagramGraphService
    {
        return new InstagramGraphService($this->config(), $this->client());
    }

    private function connectionService(): ConnectionService
    {
        return $this->connectionService ??= new ConnectionService();
    }

    private function accountService(): AccountService
    {
        return $this->accountService ??= new AccountService();
    }

    private function currentAccount(): Account
    {
        return $this->currentAccount ??= $this->accountService()->findOrCreateByName('default');
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
        $connection = $this->resolveCurrentConnection('facebook');

        if ($connection === null) {
            throw new InvalidArgumentException('A connected Facebook Page is required.');
        }

        if ($this->connectionService()->isTokenExpired($connection)) {
            return $this->refreshToken();
        }

        return $connection;
    }

    private function activeInstagramConnection(): Connection
    {
        $pageConnection = $this->activePageConnection();
        $connection = $this->resolveCurrentConnection('instagram') ?? $this->currentInstagramConnection();

        if ($connection === null) {
            throw new InvalidArgumentException('A connected Instagram Business account is required.');
        }

        if ($this->connectionService()->isTokenExpired($connection)) {
            $this->refreshToken();
            $connection = $this->currentInstagramConnection();
        }

        if ($connection === null) {
            throw new InvalidArgumentException('A connected Instagram Business account is required.');
        }

        if (! is_string($connection->access_token) || trim($connection->access_token) === '') {
            $connection = $this->connectionService()->updateTokens(
                connectionId: $this->connectionId($connection),
                accessToken: $this->stringProperty($pageConnection->access_token, 'Facebook Page access token is missing.'),
                tokenExpiresAt: is_string($pageConnection->token_expires_at) ? $pageConnection->token_expires_at : null
            );
            $this->currentInstagramConnection = $connection;
        }

        return $connection;
    }

    private function requiredPageConnection(): Connection
    {
        $connection = $this->resolveCurrentConnection('facebook');

        if ($connection === null) {
            throw new InvalidArgumentException('A connected Facebook Page is required.');
        }

        return $connection;
    }

    private function resolveCurrentConnection(string $provider): ?Connection
    {
        
        $connection = $this->connectionService()->currentConnection($this->accountId(), $provider);

        if ($provider === 'facebook') {
            $this->currentConnection = $connection;
        }

        if ($provider === 'instagram') {
            $this->currentInstagramConnection = $connection;
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

    private function accountId(): int|string
    {
        $accountId = $this->currentAccount()->social_account_id;

        if (! is_int($accountId) && ! is_string($accountId)) {
            throw new InvalidArgumentException('Account id is missing.');
        }

        return $accountId;
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

    /**
     * @param list<string>|string|null $fields
     */
    private function options(array|string|null $fields = null, ?int $limit = null, ?string $after = null, ?string $before = null): GraphRequestOptions
    {
        return GraphRequestOptions::make($fields, $limit, $after, $before);
    }

    private function assertFacebookProvider(): void
    {
        if ($this->provider !== 'facebook') {
            throw new InvalidArgumentException('Facebook page discovery requires the facebook provider.');
        }
    }
}
