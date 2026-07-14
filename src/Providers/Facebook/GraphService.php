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
use TheFoxLab\TflSocial\Providers\Meta\GraphFields;
use TheFoxLab\TflSocial\Providers\Meta\GraphRequestOptions;
use TheFoxLab\TflSocial\Providers\Meta\GraphResponse;

use function implode;
use function is_array;
use function is_int;
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
            ($options ?? GraphRequestOptions::make())->withDefaultFields(GraphFields::facebookPage())
        )));
    }

    public function edge(Connection $pageConnection, string $edge, ?GraphRequestOptions $options = null): GraphCollection
    {
        return GraphCollection::fromPayload($this->payload($this->get(
            '/' . $this->externalId($pageConnection) . '/' . $this->edgeName($edge),
            $pageConnection,
            $this->optionsWithDefaultFields($edge, $options)
        )));
    }

    private function optionsWithDefaultFields(string $edge, ?GraphRequestOptions $options): GraphRequestOptions
    {
        return ($options ?? GraphRequestOptions::make())->withDefaultFields($this->defaultFieldsForEdge($edge));
    }

    /**
     * @return list<string>
     */
    private function defaultFieldsForEdge(string $edge): array
    {
        return match ($this->edgeName($edge)) {
            'feed' => GraphFields::facebookFeed(),
            'posts' => GraphFields::facebookPosts(),
            'photos' => GraphFields::facebookPhotos(),
            'videos' => GraphFields::facebookVideos(),
            'albums' => GraphFields::facebookAlbums(),
            'events' => GraphFields::facebookEvents(),
            'ratings' => GraphFields::facebookReviews(),
            default => [],
        };
    }

    private function edgeName(string $edge): string
    {
        return trim($edge, '/');
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
            $payload = $this->payloadOrNull($response);

            if ($this->hasGraphError($payload)) {
                throw OAuthException::requestFailed($this->graphErrorMessage($response, $payload));
            }

            if ($payload !== null && $this->isEmptyGraphCollection($payload)) {
                return $response;
            }

            throw OAuthException::requestFailed($this->graphErrorMessage($response, $payload));
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

    /**
     * @return array<string, mixed>|null
     */
    private function payloadOrNull(Response $response): ?array
    {
        try {
            return $response->json();
        } catch (JsonException) {
            return null;
        }
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function isEmptyGraphCollection(array $payload): bool
    {
        return isset($payload['data']) && is_array($payload['data']) && $payload['data'] === [];
    }

    /**
     * @param array<string, mixed>|null $payload
     */
    private function hasGraphError(?array $payload): bool
    {
        return is_array($payload['error'] ?? null);
    }

    /**
     * @param array<string, mixed>|null $payload
     */
    private function graphErrorMessage(Response $response, ?array $payload): string
    {
        $error = is_array($payload['error'] ?? null) ? $payload['error'] : null;

        if ($error === null) {
            return sprintf(
                'Facebook Graph request failed with status code [%d].',
                $response->statusCode()
            );
        }

        $parts = [];
        $message = $error['message'] ?? null;
        $type = $error['type'] ?? null;
        $code = $error['code'] ?? null;
        $subcode = $error['error_subcode'] ?? null;
        $trace = $error['fbtrace_id'] ?? null;

        if (is_string($message) && trim($message) !== '') {
            $parts[] = trim($message);
        }

        if (is_string($type) && trim($type) !== '') {
            $parts[] = 'type=' . trim($type);
        }

        if (is_int($code)) {
            $parts[] = 'code=' . $code;
        }

        if (is_int($subcode)) {
            $parts[] = 'subcode=' . $subcode;
        }

        if (is_string($trace) && trim($trace) !== '') {
            $parts[] = 'fbtrace_id=' . trim($trace);
        }

        if ($parts === []) {
            return sprintf(
                'Facebook Graph request failed with status code [%d].',
                $response->statusCode()
            );
        }

        return sprintf(
            'Facebook Graph request failed with status code [%d]: %s',
            $response->statusCode(),
            implode('; ', $parts)
        );
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
