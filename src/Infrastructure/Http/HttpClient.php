<?php
declare(strict_types=1);
namespace Harbor\DigitalBankingLab\Infrastructure\Http;

interface HttpClient
{
    /** @param array<string, string> $headers */
    public function request(string $method, string $url, array $headers = []): HttpResponse;
}
