<?php

declare(strict_types=1);

namespace TheFoxLab\TflSocial;

use InvalidArgumentException;
use TheFoxLab\TflSocial\Config\TflSocial;
use TheFoxLab\TflSocial\Contracts\ConnectorInterface;
use TheFoxLab\TflSocial\Http\ClientInterface;
use TheFoxLab\TflSocial\Http\Factory;
use TheFoxLab\TflSocial\Providers\Facebook\OAuth as FacebookOAuth;
use TheFoxLab\TflSocial\Providers\Facebook\OAuthResponse as FacebookOAuthResponse;
use TheFoxLab\TflSocial\Providers\Facebook\Page;
use TheFoxLab\TflSocial\Providers\Facebook\PageCollection;
use TheFoxLab\TflSocial\Providers\Facebook\PageService;
use TheFoxLab\TflSocial\Providers\Instagram\OAuth as InstagramOAuth;
use TheFoxLab\TflSocial\Providers\Instagram\OAuthResponse as InstagramOAuthResponse;

use function strtolower;
use function trim;

final class Connector implements ConnectorInterface
{
    private ?string $provider = null;

    private ?FacebookOAuthResponse $facebookOAuthResponse = null;

    public function __construct(
        private ?TflSocial $config = null,
        private ?ClientInterface $client = null
    ) {
    }

    public function provider(string $provider): self
    {
        $provider = strtolower(trim($provider));

        if ($provider === '') {
            throw new InvalidArgumentException('Provider name cannot be empty.');
        }

        $this->provider = $provider;

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
        return $this->client ??= Factory::create($this->config());
    }

    private function pageService(): PageService
    {
        return new PageService($this->config(), $this->client());
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
