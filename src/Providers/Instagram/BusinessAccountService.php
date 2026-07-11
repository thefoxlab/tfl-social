<?php

declare(strict_types=1);

namespace TheFoxLab\TflSocial\Providers\Instagram;

use JsonException;
use TheFoxLab\TflSocial\Config\TflSocial;
use TheFoxLab\TflSocial\Entities\Connection;
use TheFoxLab\TflSocial\Http\Client;
use TheFoxLab\TflSocial\Http\ClientInterface;
use TheFoxLab\TflSocial\Http\HttpException;
use TheFoxLab\TflSocial\Http\Response;

use function is_array;
use function is_string;
use function sprintf;
use function trim;

final class BusinessAccountService
{
    private const GRAPH_BASE_URL = 'https://graph.facebook.com';

    public function __construct(
        private readonly TflSocial $config = new TflSocial(),
        ?ClientInterface $client = null
    ) {
        $this->client = $client ?? new Client($this->config);
    }

    private readonly ClientInterface $client;

    public function businessAccounts(Connection $pageConnection): BusinessAccountCollection
    {
        $account = $this->businessAccount($pageConnection);

        return $account === null
            ? new BusinessAccountCollection()
            : new BusinessAccountCollection([$account]);
    }

    public function businessAccount(Connection $pageConnection): ?BusinessAccount
    {
        $payload = $this->payload($this->get(
            '/' . $this->externalId($pageConnection),
            $this->accessToken($pageConnection)
        ));

        $account = $payload['instagram_business_account'] ?? null;

        if (! is_array($account)) {
            return null;
        }

        return $this->mapAccount($account);
    }

    private function get(string $uri, string $accessToken): Response
    {
        try {
            $response = $this->client->get($uri, [
                'base_url' => self::GRAPH_BASE_URL . '/' . $this->graphVersion(),
                'bearer_token' => $accessToken,
                'query' => [
                    'fields' => 'instagram_business_account{id,username,name,profile_picture_url}',
                ],
            ]);
        } catch (HttpException $exception) {
            throw OAuthException::configuration($exception->getMessage());
        }

        if (! $response->successful()) {
            throw OAuthException::configuration(sprintf(
                'Instagram Business discovery failed with status code [%d].',
                $response->statusCode()
            ));
        }

        return $response;
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(Response $response): array
    {
        try {
            return $response->json();
        } catch (JsonException $exception) {
            throw OAuthException::configuration($exception->getMessage());
        }
    }

    /**
     * @param array<string, mixed> $account
     */
    private function mapAccount(array $account): BusinessAccount
    {
        $accountId = $account['id'] ?? null;

        if (! is_string($accountId) || trim($accountId) === '') {
            throw OAuthException::configuration('Instagram Business account id is missing.');
        }

        return new BusinessAccount(
            accountId: $accountId,
            username: $this->optionalString($account['username'] ?? null),
            name: $this->optionalString($account['name'] ?? null),
            profilePicture: $this->optionalString($account['profile_picture_url'] ?? null)
        );
    }

    private function externalId(Connection $connection): string
    {
        $externalId = $connection->external_id;

        if (! is_string($externalId) || trim($externalId) === '') {
            throw OAuthException::configuration('Facebook Page external id is required for Instagram discovery.');
        }

        return $externalId;
    }

    private function accessToken(Connection $connection): string
    {
        $accessToken = $connection->access_token;

        if (! is_string($accessToken) || trim($accessToken) === '') {
            throw OAuthException::configuration('Facebook Page access token is required for Instagram discovery.');
        }

        return $accessToken;
    }

    private function graphVersion(): string
    {
        $version = trim($this->config->graphVersion);

        if ($version === '') {
            throw OAuthException::configuration('Meta Graph version is not configured.');
        }

        return $version;
    }

    private function optionalString(mixed $value): ?string
    {
        return is_string($value) && trim($value) !== '' ? $value : null;
    }
}
