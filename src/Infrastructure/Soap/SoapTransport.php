<?php
declare(strict_types=1);
namespace Harbor\DigitalBankingLab\Infrastructure\Soap;

interface SoapTransport
{
    public function send(string $endpoint, string $soapAction, string $xmlBody): SoapTransportResponse;
}
