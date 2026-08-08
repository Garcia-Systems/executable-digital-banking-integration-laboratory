<?php
declare(strict_types=1);
namespace Harbor\DigitalBankingLab\Infrastructure\Http;

final readonly class HttpResponse
{
    /** @param array<string, string> $headers */
    public function __construct(public int $statusCode, public array $headers, public string $body) {}
}
