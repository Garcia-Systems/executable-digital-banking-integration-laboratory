<?php
declare(strict_types=1);
namespace Harbor\DigitalBankingLab\Infrastructure\Http;

/** A fixture transport: it records requests and can never open a network connection. */
final class DeterministicHttpClient implements HttpClient
{
    /** @var list<array{method: string, url: string, headers: array<string, string>}> */
    private array $requests = [];

    public function __construct(private readonly string $scenario, private readonly string $fixtureDirectory) {}

    public function request(string $method, string $url, array $headers = []): HttpResponse
    {
        $this->requests[] = compact('method', 'url', 'headers');
        if ($this->scenario === 'vendor-timeout') {
            throw new HttpTimeoutException('Northstar request timed out.');
        }
        if ($this->scenario === 'vendor-unavailable') {
            throw new HttpTransportException('Northstar connection was refused.');
        }
        $fixture = match ($this->scenario) {
            'normal', 'normal-operation' => ['customer-success.json', 200],
            'customer-not-found' => ['customer-not-found.json', 404],
            'vendor-error' => ['vendor-internal-error.json', 500],
            'malformed-json' => ['customer-malformed.json', 200],
            'incomplete-response' => ['customer-incomplete.json', 200],
            'unsupported-product', 'northstar-unsupported-product' => ['customer-unsupported-product.json', 200],
            default => throw new \InvalidArgumentException("Unknown vendor REST scenario: {$this->scenario}"),
        };
        $body = file_get_contents($this->fixtureDirectory . '/' . $fixture[0]);
        if ($body === false) throw new \LogicException("Missing deterministic HTTP fixture: {$fixture[0]}");
        return new HttpResponse($fixture[1], ['Content-Type' => 'application/json'], $body);
    }

    /** @return list<array{method: string, url: string, headers: array<string, string>}> */
    public function requests(): array { return $this->requests; }
}
