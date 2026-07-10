<?php

declare(strict_types=1);

namespace TheFoxLab\TflSocial\Http;

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
        return $this->request('GET', $uri, $options);
    }

    /**
     * @param array<string, mixed> $options
     */
    public function post(string $uri, array $options = []): Response
    {
        return $this->request('POST', $uri, $options);
    }

    /**
     * @param array<string, mixed> $options
     */
    public function put(string $uri, array $options = []): Response
    {
        return $this->request('PUT', $uri, $options);
    }

    /**
     * @param array<string, mixed> $options
     */
    public function patch(string $uri, array $options = []): Response
    {
        return $this->request('PATCH', $uri, $options);
    }

    /**
     * @param array<string, mixed> $options
     */
    public function delete(string $uri, array $options = []): Response
    {
        return $this->request('DELETE', $uri, $options);
    }

    /**
     * @param array<string, mixed> $options
     */
    public function request(string $method, string $uri, array $options = []): Response
    {
        try {
            $response = service('curlrequest', $this->baseOptions($options))
                ->request($method, $this->resolveUri($uri, $options), $this->requestOptions($options));

            return $this->buildResponse($response);
        } catch (Throwable $exception) {
            throw HttpException::transport($exception->getMessage(), $exception);
        }
    }

    /**
     * @param array<string, mixed> $options
     *
     * @return array<string, mixed>
     */
    private function baseOptions(array $options): array
    {
        $baseOptions = [];
        $timeout = $options['timeout'] ?? $this->configValue('timeout');
        $connectTimeout = $options['connect_timeout'] ?? $options['connectTimeout'] ?? $this->configValue('connectTimeout');
        $verify = $options['verify'] ?? $options['verify_ssl'] ?? $options['verifySsl'] ?? $this->configValue('verifySSL');

        if (is_int($timeout) || is_float($timeout)) {
            $baseOptions['timeout'] = $timeout;
        }

        if (is_int($connectTimeout) || is_float($connectTimeout)) {
            $baseOptions['connect_timeout'] = $connectTimeout;
        }

        if (is_bool($verify)) {
            $baseOptions['verify'] = $verify;
        }

        return $baseOptions;
    }

    /**
     * @param array<string, mixed> $options
     *
     * @return array<string, mixed>
     */
    private function requestOptions(array $options): array
    {
        $requestOptions = [
            'http_errors' => false,
        ];

        $headers = is_array($options['headers'] ?? null) ? $options['headers'] : [];
        $bearerToken = $options['bearer_token'] ?? $options['bearerToken'] ?? null;
        $userAgent = $options['user_agent'] ?? $options['userAgent'] ?? $this->configValue('userAgent');

        if (is_string($bearerToken) && $bearerToken !== '') {
            $headers['Authorization'] = 'Bearer ' . $bearerToken;
        }

        if (is_string($userAgent) && $userAgent !== '') {
            $headers['User-Agent'] = $userAgent;
        }

        if ($headers !== []) {
            $requestOptions['headers'] = $headers;
        }

        foreach (['query', 'json', 'multipart'] as $key) {
            if (is_array($options[$key] ?? null)) {
                $requestOptions[$key] = $options[$key];
            }
        }

        $form = $options['form_params'] ?? $options['form'] ?? null;

        if (is_array($form)) {
            $requestOptions['form_params'] = $form;
        }

        return $requestOptions;
    }

    /**
     * @param array<string, mixed> $options
     */
    private function resolveUri(string $uri, array $options): string
    {
        if (str_starts_with($uri, 'http://') || str_starts_with($uri, 'https://')) {
            return $uri;
        }

        $baseUrl = $options['base_url'] ?? $options['baseUrl'] ?? $this->configValue('baseUrl');

        if (! is_string($baseUrl) || $baseUrl === '') {
            return $uri;
        }

        return rtrim($baseUrl, '/') . '/' . ltrim($uri, '/');
    }

    private function buildResponse(ResponseInterface $response): Response
    {
        return new Response(
            statusCode: $response->getStatusCode(),
            body: (string) $response->getBody(),
            headers: $this->headers($response),
            reason: $response->getReasonPhrase()
        );
    }

    /**
     * @return array<string, list<string>>
     */
    private function headers(ResponseInterface $response): array
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

    private function configValue(string $key): mixed
    {
        return $this->config->http[$key] ?? null;
    }
}
