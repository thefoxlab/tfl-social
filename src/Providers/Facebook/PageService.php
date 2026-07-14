<?php

declare(strict_types=1);

namespace TheFoxLab\TflSocial\Providers\Facebook;

use JsonException;
use TheFoxLab\TflSocial\Config\TflSocial;
use TheFoxLab\TflSocial\Http\Client;
use TheFoxLab\TflSocial\Http\ClientInterface;
use TheFoxLab\TflSocial\Http\HttpException;
use TheFoxLab\TflSocial\Http\Response;
use TheFoxLab\TflSocial\Providers\Meta\GraphFields;

use function implode;
use function is_array;
use function is_string;
use function sprintf;
use function trim;

final class PageService
{
    private const GRAPH_BASE_URL = 'https://graph.facebook.com';

    public function __construct(
        private readonly TflSocial $config = new TflSocial(),
        ?ClientInterface $client = null
    ) {
        $this->client = $client ?? new Client($this->config);
    }

    private readonly ClientInterface $client;

    public function pages(OAuthResponse $oauthResponse): PageCollection
    {
        $payload = $this->payload($this->get('/me/accounts', $this->accessToken($oauthResponse)));
        $data = is_array($payload['data'] ?? null) ? $payload['data'] : [];
        $pages = [];

        foreach ($data as $page) {
            if (is_array($page)) {
                $pages[] = $this->mapPage($page);
            }
        }

        return new PageCollection($pages);
    }

    public function page(string $pageId, OAuthResponse $oauthResponse): Page
    {
        $page = $this->pages($oauthResponse)->find($pageId);

        if ($page !== null) {
            return $page;
        }

        $payload = $this->payload($this->get(
            '/' . $pageId,
            $this->accessToken($oauthResponse)
        ));

        if (! is_array($payload) || empty($payload['access_token'])) {
            throw OAuthException::invalidResponse(
                sprintf('Facebook page [%s] was not found or you lack access.', $pageId)
            );
        }

        return $this->mapPage($payload);
    }

    public function businessPages(OAuthResponse $oauthResponse): PageCollection
    {
        $businesses = $this->payload($this->client->get('/me/businesses', [
            'base_url' => self::GRAPH_BASE_URL . '/' . $this->graphVersion(),
            'bearer_token' => $this->accessToken($oauthResponse),
            'query' => ['fields' => $this->fields(GraphFields::facebookBusinesses())],
        ]));

        $pages = [];
        $seenPageIds = [];

        foreach ($businesses['data'] ?? [] as $biz) {
            $owned = $this->payload($this->client->get('/' . $biz['id'] . '/owned_pages', [
                'base_url' => self::GRAPH_BASE_URL . '/' . $this->graphVersion(),
                'bearer_token' => $this->accessToken($oauthResponse),
                'query' => ['fields' => $this->fields(GraphFields::facebookPageDiscovery())],
            ]));

            $clients = $this->payload($this->client->get('/' . $biz['id'] . '/client_pages', [
                'base_url' => self::GRAPH_BASE_URL . '/' . $this->graphVersion(),
                'bearer_token' => $this->accessToken($oauthResponse),
                'query' => ['fields' => $this->fields(GraphFields::facebookPageDiscovery())],
            ]));

            foreach (array_merge($owned['data'] ?? [], $clients['data'] ?? []) as $page) {
                if (! is_array($page) || empty($page['id']) || empty($page['access_token'])) {
                    continue;
                }

                $pageId = (string) $page['id'];

                if (isset($seenPageIds[$pageId])) {
                    continue;
                }

                $seenPageIds[$pageId] = true;
                $pages[] = $this->mapPage($page);
            }
        }

        return new PageCollection($pages);
    }

    private function get(string $uri, string $accessToken): Response
    {
        try {
            $response = $this->client->get($uri, [
                'base_url' => self::GRAPH_BASE_URL . '/' . $this->graphVersion(),
                'bearer_token' => $accessToken,
                'query' => [
                    'fields' => $this->fields(GraphFields::facebookPageDiscovery()),
                ],
            ]);
        } catch (HttpException $exception) {
            throw OAuthException::requestFailed($exception->getMessage(), $exception);
        }

        if (! $response->successful()) {
            throw OAuthException::requestFailed(sprintf(
                'Facebook page discovery failed with status code [%d].',
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

    /**
     * @param array<string, mixed> $data
     */
    private function mapPage(array $data): Page
    {
        $pageId = $this->stringValue($data['id'] ?? null, 'Page id is missing.');
        $name = $this->stringValue($data['name'] ?? null, 'Page name is missing.');
        $accessToken = $this->stringValue($data['access_token'] ?? null, 'Page access token is missing.');
        $picture = null;

        if (is_array($data['picture'] ?? null) && is_array($data['picture']['data'] ?? null)) {
            $pictureUrl = $data['picture']['data']['url'] ?? null;
            $picture = is_string($pictureUrl) && $pictureUrl !== '' ? $pictureUrl : null;
        }

        return new Page(
            pageId: $pageId,
            name: $name,
            accessToken: $accessToken,
            category: is_string($data['category'] ?? null) ? $data['category'] : null,
            tasks: $this->tasks($data['tasks'] ?? []),
            picture: $picture
        );
    }

    /**
     * @return list<string>
     */
    private function tasks(mixed $tasks): array
    {
        if (! is_array($tasks)) {
            return [];
        }

        $values = [];

        foreach ($tasks as $task) {
            if (is_string($task) && $task !== '') {
                $values[] = $task;
            }
        }

        return $values;
    }

    private function accessToken(OAuthResponse $oauthResponse): string
    {
        $accessToken = $oauthResponse->accessToken();

        if ($accessToken === null || trim($accessToken) === '') {
            throw OAuthException::configuration('A Facebook OAuth access token is required for page discovery.');
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

    /**
     * @param list<string> $fields
     */
    private function fields(array $fields): string
    {
        return implode(',', $fields);
    }

    private function stringValue(mixed $value, string $message): string
    {
        if (! is_string($value) || trim($value) === '') {
            throw OAuthException::invalidResponse($message);
        }

        return $value;
    }
}
