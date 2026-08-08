<?php
declare(strict_types=1);
namespace Harbor\DigitalBankingLab\Infrastructure\Soap;

final readonly class SoapTransportResponse
{
    public function __construct(public int $statusCode, public string $xmlBody) {}
}
