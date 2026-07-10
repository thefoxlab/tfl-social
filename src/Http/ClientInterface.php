<?php

declare(strict_types=1);

namespace TheFoxLab\TflSocial\Http;

interface ClientInterface
{
    /**
     * @param array<string, mixed> $options
     */
    public function get(string $uri, array $options = []): Response;

    /**
     * @param array<string, mixed> $options
     */
    public function post(string $uri, array $options = []): Response;

    /**
     * @param array<string, mixed> $options
     */
    public function put(string $uri, array $options = []): Response;

    /**
     * @param array<string, mixed> $options
     */
    public function patch(string $uri, array $options = []): Response;

    /**
     * @param array<string, mixed> $options
     */
    public function delete(string $uri, array $options = []): Response;

    public function send(Request $request): Response;
}
