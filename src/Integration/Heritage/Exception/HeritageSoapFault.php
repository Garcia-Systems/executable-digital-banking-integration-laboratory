<?php
declare(strict_types=1);
namespace Harbor\DigitalBankingLab\Integration\Heritage\Exception;

class HeritageSoapFault extends \RuntimeException
{
    public function __construct(public readonly string $faultCode, public readonly int $httpStatus, string $message)
    { parent::__construct("Heritage SOAP Fault {$faultCode}: {$message} (HTTP {$httpStatus})"); }
}
