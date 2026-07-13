<?php

declare(strict_types=1);

namespace TheFoxLab\TflSocial\Providers\Facebook;

use JsonException;
use TheFoxLab\TflSocial\Config\TflSocial;
use TheFoxLab\TflSocial\Entities\Connection;
use TheFoxLab\TflSocial\Http\Client;
use TheFoxLab\TflSocial\Http\ClientInterface;
use TheFoxLab\TflSocial\Http\HttpException;
use TheFoxLab\TflSocial\Http\Response;
use TheFoxLab\TflSocial\Providers\Meta\GraphCollection;
use TheFoxLab\TflSocial\Providers\Meta\GraphRequestOptions;
use TheFoxLab\TflSocial\Providers\Meta\GraphResponse;

use function is_string;
use function sprintf;
use function trim;

final class GraphService
{
    private const GRAPH_BASE_URL = 'https://graph.facebook.com';

    public function __construct(
        private readonly TflSocial $config = new TflSocial(),
        ?ClientInterface $client = null
    ) {
        $this->client = $client ?? new Client($this->config);
    }

    private readonly ClientInterface $client;

    public function node(Connection $pageConnection, ?GraphRequestOptions $options = null): GraphResponse
    {
        return new GraphResponse($this->payload($this->get(
            '/' . $this->externalId($pageConnection),
            $pageConnection,
            $options ?? GraphRequestOptions::make(['id', 'name', 'category', 'picture{url}'])
        )));
    }

    public function edge(Connection $pageConnection, string $edge, ?GraphRequestOptions $options = null): GraphCollection
    {
        return GraphCollection::fromPayload($this->payload($this->get(
            '/' . $this->externalId($pageConnection) . '/' . trim($edge, '/'),
            $pageConnection,
            $options ?? GraphRequestOptions::make()
        )));
    }

    private function get(string $uri, Connection $connection, GraphRequestOptions $options): Response
    {
        try {
            $response = $this->client->get($uri, [
                'base_url' => self::GRAPH_BASE_URL . '/' . $this->graphVersion(),
                'bearer_token' => $this->accessToken($connection),
                'query' => $options->query(),
            ]);
        } catch (HttpException $exception) {
            throw OAuthException::requestFailed($exception->getMessage(), $exception);
        }

        if (! $response->successful()) {
            throw OAuthException::requestFailed(sprintf(
                'Facebook Graph request failed with status code [%d].',
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
            throw OAuthException::invalidResponse($exception->getMessage());
        }
    }

    private function externalId(Connection $connection): string
    {
        $externalId = $connection->external_id;

        if (! is_string($externalId) || trim($externalId) === '') {
            throw OAuthException::configuration('Facebook Page external id is required.');
        }

        return $externalId;
    }

    private function accessToken(Connection $connection): string
    {
        $accessToken = $connection->access_token;

        if (! is_string($accessToken) || trim($accessToken) === '') {
            throw OAuthException::configuration('Facebook Page access token is required.');
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
}
