<?php
declare(strict_types=1);
namespace Harbor\DigitalBankingLab\Integration\Northstar\Exception;
class NorthstarHttpFailure extends \RuntimeException
{
    public function __construct(public readonly int $statusCode) { parent::__construct("Northstar returned HTTP {$statusCode}."); }
}
