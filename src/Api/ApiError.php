<?php

declare(strict_types=1);

namespace Harbor\DigitalBankingLab\Api;

final readonly class ApiError
{
    public function __construct(public string $code, public string $message)
    {
    }

    /** @return array{error: array{code: string, message: string}} */
    public function toArray(): array
    {
        return ['error' => ['code' => $this->code, 'message' => $this->message]];
    }
}
