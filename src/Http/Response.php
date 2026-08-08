<?php

declare(strict_types=1);

namespace Harbor\DigitalBankingLab\Http;

final readonly class Response
{
    /** @param array<string, string> $headers */
    public function __construct(public int $status, public array $headers, public string $body)
    {
    }

    /** @param array<string, mixed> $data */
    public static function json(int $status, array $data): self
    {
        return new self($status, ['Content-Type' => 'application/json; charset=utf-8'], json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES) . "\n");
    }

    public function send(): void
    {
        http_response_code($this->status);
        foreach ($this->headers as $name => $value) {
            header("{$name}: {$value}");
        }
        echo $this->body;
    }
}
