<?php

declare(strict_types=1);

namespace TheFoxLab\TflSocial\Http;

final class Request
{
    /**
     * @param array<string, string> $headers
     * @param array<string, mixed> $query
     * @param array<string, mixed>|null $json
     * @param array<string, mixed>|null $form
     * @param array<int|string, mixed>|null $multipart
     */
    public function __construct(
        private readonly string $method,
        private readonly string $uri,
        private readonly ?string $baseUrl = null,
        private readonly array $headers = [],
        private readonly array $query = [],
        private readonly ?array $json = null,
        private readonly ?array $form = null,
        private readonly ?array $multipart = null,
        private readonly ?string $bearerToken = null,
        private readonly ?float $timeout = null,
        private readonly ?float $connectTimeout = null,
        private readonly ?bool $verifySsl = null,
        private readonly ?string $userAgent = null
    ) {
    }

    /**
     * @param array<string, mixed> $options
     */
    public static function fromArray(string $method, string $uri, array $options = []): self
    {
        return new self(
            method: $method,
            uri: $uri,
            baseUrl: self::stringOrNull($options['base_url'] ?? $options['baseUrl'] ?? null),
            headers: self::arrayOrEmpty($options['headers'] ?? []),
            query: self::arrayOrEmpty($options['query'] ?? []),
            json: self::arrayOrNull($options['json'] ?? null),
            form: self::arrayOrNull($options['form'] ?? $options['form_params'] ?? null),
            multipart: self::arrayOrNull($options['multipart'] ?? null),
            bearerToken: self::stringOrNull($options['bearer_token'] ?? $options['bearerToken'] ?? null),
            timeout: self::floatOrNull($options['timeout'] ?? null),
            connectTimeout: self::floatOrNull($options['connect_timeout'] ?? $options['connectTimeout'] ?? null),
            verifySsl: self::boolOrNull($options['verify_ssl'] ?? $options['verifySsl'] ?? null),
            userAgent: self::stringOrNull($options['user_agent'] ?? $options['userAgent'] ?? null)
        );
    }

    public function method(): string
    {
        return strtoupper($this->method);
    }

    public function uri(): string
    {
        return $this->uri;
    }

    public function baseUrl(): ?string
    {
        return $this->baseUrl;
    }

    /**
     * @return array<string, string>
     */
    public function headers(): array
    {
        return $this->headers;
    }

    /**
     * @return array<string, mixed>
     */
    public function query(): array
    {
        return $this->query;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function json(): ?array
    {
        return $this->json;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function form(): ?array
    {
        return $this->form;
    }

    /**
     * @return array<int|string, mixed>|null
     */
    public function multipart(): ?array
    {
        return $this->multipart;
    }

    public function bearerToken(): ?string
    {
        return $this->bearerToken;
    }

    public function timeout(): ?float
    {
        return $this->timeout;
    }

    public function connectTimeout(): ?float
    {
        return $this->connectTimeout;
    }

    public function verifySsl(): ?bool
    {
        return $this->verifySsl;
    }

    public function userAgent(): ?string
    {
        return $this->userAgent;
    }

    private static function stringOrNull(mixed $value): ?string
    {
        return is_string($value) && $value !== '' ? $value : null;
    }

    /**
     * @return array<string, mixed>
     */
    private static function arrayOrEmpty(mixed $value): array
    {
        return is_array($value) ? $value : [];
    }

    /**
     * @return array<mixed>|null
     */
    private static function arrayOrNull(mixed $value): ?array
    {
        return is_array($value) ? $value : null;
    }

    private static function floatOrNull(mixed $value): ?float
    {
        return is_int($value) || is_float($value) ? (float) $value : null;
    }

    private static function boolOrNull(mixed $value): ?bool
    {
        return is_bool($value) ? $value : null;
    }
}
