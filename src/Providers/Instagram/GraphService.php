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
use TheFoxLab\TflSocial\Providers\Meta\FeatureUnavailableResponse;
use TheFoxLab\TflSocial\Providers\Meta\GraphCollection;
use TheFoxLab\TflSocial\Providers\Meta\GraphFields;
use TheFoxLab\TflSocial\Providers\Meta\GraphItem;
use TheFoxLab\TflSocial\Providers\Meta\GraphRequestOptions;
use TheFoxLab\TflSocial\Providers\Meta\GraphResponse;

use function is_array;
use function is_string;
use function sprintf;
use function str_contains;
use function strtolower;
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

    public function profile(Connection $connection, ?GraphRequestOptions $options = null): GraphResponse
    {
        return new GraphResponse($this->payload($this->get(
            '/' . $this->externalId($connection),
            $connection,
            ($options ?? GraphRequestOptions::make())->withDefaultFields(GraphFields::instagramProfile())
        )));
    }

    public function media(Connection $connection, ?GraphRequestOptions $options = null): GraphCollection
    {
        return GraphCollection::fromPayload($this->payload($this->get(
            '/' . $this->externalId($connection) . '/media',
            $connection,
            ($options ?? GraphRequestOptions::make())->withDefaultFields(GraphFields::instagramMedia())
        )));
    }

    public function mediaById(Connection $connection, string $mediaId, ?GraphRequestOptions $options = null): GraphResponse
    {
        return new GraphResponse($this->payload($this->get(
            '/' . $mediaId,
            $connection,
            ($options ?? GraphRequestOptions::make())->withDefaultFields(GraphFields::instagramMediaById())
        )));
    }

    public function reels(Connection $connection, ?GraphRequestOptions $options = null): GraphCollection
    {
        return $this->mediaByType(
            $connection,
            ['REELS'],
            ($options ?? GraphRequestOptions::make())->withDefaultFields(GraphFields::instagramReels())
        );
    }

    public function carousel(Connection $connection, ?GraphRequestOptions $options = null): GraphCollection
    {
        return $this->mediaByType(
            $connection,
            ['CAROUSEL_ALBUM'],
            ($options ?? GraphRequestOptions::make())->withDefaultFields(GraphFields::instagramCarousel())
        );
    }

    public function stories(Connection $connection, ?GraphRequestOptions $options = null): GraphCollection|FeatureUnavailableResponse
    {
        $response = $this->tryGet(
            '/' . $this->externalId($connection) . '/stories',
            $connection,
            ($options ?? GraphRequestOptions::make())->withDefaultFields(GraphFields::instagramStories())
        );

        return $response instanceof FeatureUnavailableResponse
            ? $response
            : GraphCollection::fromPayload($this->payload($response));
    }


    public function hashtagSearch(Connection $connection, string $hashtag): GraphResponse|FeatureUnavailableResponse
    {
        $response = $this->tryGet(
            '/ig_hashtag_search',
            $connection,
            GraphRequestOptions::make()->withDefaultFields(GraphFields::instagramHashtagSearch()),
            [
                'user_id' => $this->externalId($connection),
                'q' => trim($hashtag),
            ]
        );

        return $response instanceof FeatureUnavailableResponse
            ? $response
            : new GraphResponse($this->payload($response));
    }

    public function recentHashtagMedia(
        Connection $connection,
        string $hashtagId,
        ?GraphRequestOptions $options = null
    ): GraphCollection|FeatureUnavailableResponse {
        $response = $this->tryGet(
            '/' . $hashtagId . '/recent_media',
            $connection,
            ($options ?? GraphRequestOptions::make())->withDefaultFields(GraphFields::instagramHashtagMedia()),
            [
                'user_id' => $this->externalId($connection),
            ]
        );

        return $response instanceof FeatureUnavailableResponse
            ? $response
            : GraphCollection::fromPayload($this->payload($response));
    }

    public function ownMediaByHashtag(Connection $connection, string $hashtag, ?GraphRequestOptions $options = null): GraphCollection
    {
        $media = $this->media($connection, $options);
        $items = [];
        $needle = strtolower('#' . ltrim(trim($hashtag), '#'));

        foreach ($media as $item) {
            $caption = $item->get('caption');

            if (is_string($caption) && str_contains(strtolower($caption), $needle)) {
                $items[] = $item;
            }
        }

        return new GraphCollection($items, $media->pagination());
    }

    /**
     * @param list<string> $types
     */
    private function mediaByType(Connection $connection, array $types, GraphRequestOptions $options): GraphCollection
    {
        $media = $this->media($connection, $options);
        $items = [];

        foreach ($media as $item) {
            if ($this->matchesMediaType($item, $types)) {
                $items[] = $item;
            }
        }

        return new GraphCollection($items, $media->pagination());
    }

    /**
     * @param list<string> $types
     */
    private function matchesMediaType(GraphItem $item, array $types): bool
    {
        $mediaType = $item->get('media_type');
        $productType = $item->get('media_product_type');

        foreach ([$mediaType, $productType] as $value) {
            if (is_string($value) && in_array($value, $types, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<string, mixed> $query
     */
    private function tryGet(
        string $uri,
        Connection $connection,
        GraphRequestOptions $options,
        array $query = []
    ): Response|FeatureUnavailableResponse {
        try {
            return $this->get($uri, $connection, $options, $query);
        } catch (OAuthException $exception) {
            return new FeatureUnavailableResponse($uri, $exception->getMessage());
        }
    }

    /**
     * @param array<string, mixed> $query
     */
    private function get(string $uri, Connection $connection, GraphRequestOptions $options, array $query = []): Response
    {
        try {
            $response = $this->client->get($uri, [
                'base_url' => self::GRAPH_BASE_URL . '/' . $this->graphVersion(),
                'bearer_token' => $this->accessToken($connection),
                'query' => $query + $options->query(),
            ]);
        } catch (HttpException $exception) {
            throw OAuthException::requestFailed($exception->getMessage(), $exception);
        }

        if (! $response->successful()) {
            throw OAuthException::requestFailed(sprintf(
                'Instagram Graph request failed with status code [%d].',
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
            throw OAuthException::configuration('Instagram Business external id is required.');
        }

        return $externalId;
    }

    private function accessToken(Connection $connection): string
    {
        $accessToken = $connection->access_token;

        if (! is_string($accessToken) || trim($accessToken) === '') {
            throw OAuthException::configuration('Instagram Business access token is required.');
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
