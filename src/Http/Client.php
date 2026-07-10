<?php

declare(strict_types=1);

namespace TheFoxLab\TflSocial\Http;

use CodeIgniter\HTTP\CURLRequest;
use CodeIgniter\HTTP\ResponseInterface;
use TheFoxLab\TflSocial\Config\TflSocial;
use Throwable;

final class Client implements ClientInterface
{
    public function __construct(
        private readonly TflSocial $config = new TflSocial()
    ) {
    }

    /**
     * @param array<string, mixed> $options
     */
    public function get(string $uri, array $options = []): Response
    {
        return $this->send(Request::fromArray('GET', $uri, $options));
    }

    /**
     * @param array<string, mixed> $options
     */
    public function post(string $uri, array $options = []): Response
    {
        return $this->send(Request::fromArray('POST', $uri, $options));
    }

    /**
     * @param array<string, mixed> $options
     */
    public function put(string $uri, array $options = []): Response
    {
        return $this->send(Request::fromArray('PUT', $uri, $options));
    }

    /**
     * @param array<string, mixed> $options
     */
    public function patch(string $uri, array $options = []): Response
    {
        return $this->send(Request::fromArray('PATCH', $uri, $options));
    }

    /**
     * @param array<string, mixed> $options
     */
    public function delete(string $uri, array $options = []): Response
    {
        return $this->send(Request::fromArray('DELETE', $uri, $options));
    }

    public function send(Request $request): Response
    {
        try {
            $response = $this->curlRequest()->request(
                $request->method(),
                $this->resolveUri($request),
                $this->buildOptions($request)
            );
        } catch (Throwable $exception) {
            throw HttpException::transport($exception->getMessage(), $exception);
        }

        return $this->buildResponse($response);
    }

    private function curlRequest(): CURLRequest
    {
        $services = '\\Config\\Services';

        if (! class_exists($services) || ! method_exists($services, 'curlrequest')) {
            throw HttpException::configuration('CodeIgniter CURLRequest service is not available.');
        }

        $request = $services::curlrequest([], false);

        if (! $request instanceof CURLRequest) {
            throw HttpException::configuration('CodeIgniter CURLRequest service returned an invalid client.');
        }

        return $request;
    }

    /**
     * @return array<string, mixed>
     */
    private function buildOptions(Request $request): array
    {
        $headers = $request->headers();
        $userAgent = $request->userAgent() ?? $this->stringConfig('userAgent');

        if ($request->bearerToken() !== null) {
            $headers['Authorization'] = 'Bearer ' . $request->bearerToken();
        }

        if ($userAgent !== null) {
            $headers['User-Agent'] = $userAgent;
        }

        $options = [
            'headers' => $headers,
            'http_errors' => false,
        ];

        $timeout = $request->timeout() ?? $this->floatConfig('timeout');
        $connectTimeout = $request->connectTimeout() ?? $this->floatConfig('connectTimeout');
        $verifySsl = $request->verifySsl() ?? $this->boolConfig('verifySSL');

        if ($timeout !== null) {
            $options['timeout'] = $timeout;
        }

        if ($connectTimeout !== null) {
            $options['connect_timeout'] = $connectTimeout;
        }

        if ($verifySsl !== null) {
            $options['verify'] = $verifySsl;
        }

        if ($request->query() !== []) {
            $options['query'] = $request->query();
        }

        if ($request->json() !== null) {
            $options['json'] = $request->json();
        }

        if ($request->form() !== null) {
            $options['form_params'] = $request->form();
        }

        if ($request->multipart() !== null) {
            $options['multipart'] = $request->multipart();
        }

        return $options;
    }

    private function resolveUri(Request $request): string
    {
        $uri = $request->uri();

        if (str_starts_with($uri, 'http://') || str_starts_with($uri, 'https://')) {
            return $uri;
        }

        $baseUrl = $request->baseUrl() ?? $this->stringConfig('baseUrl');

        if ($baseUrl === null) {
            return $uri;
        }

        return rtrim($baseUrl, '/') . '/' . ltrim($uri, '/');
    }

    private function buildResponse(ResponseInterface $response): Response
    {
        return new Response(
            statusCode: $response->getStatusCode(),
            body: (string) $response->getBody(),
            headers: $this->normalizeHeaders($response),
            reason: $response->getReasonPhrase()
        );
    }

    /**
     * @return array<string, list<string>>
     */
    private function normalizeHeaders(ResponseInterface $response): array
    {
        $headers = [];

        foreach ($response->getHeaders() as $name => $header) {
            if (is_object($header) && method_exists($header, 'getValueLine')) {
                $headers[$name] = [(string) $header->getValueLine()];

                continue;
            }

            if (is_array($header)) {
                $headers[$name] = array_map(static fn (mixed $value): string => (string) $value, $header);

                continue;
            }

            $headers[$name] = [(string) $header];
        }

        return $headers;
    }

    private function stringConfig(string $key): ?string
    {
        $value = $this->config->http[$key] ?? null;

        return is_string($value) && $value !== '' ? $value : null;
    }

    private function floatConfig(string $key): ?float
    {
        $value = $this->config->http[$key] ?? null;

        return is_int($value) || is_float($value) ? (float) $value : null;
    }

    private function boolConfig(string $key): ?bool
    {
        $value = $this->config->http[$key] ?? null;

        return is_bool($value) ? $value : null;
    }
}
