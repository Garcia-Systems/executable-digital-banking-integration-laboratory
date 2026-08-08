<?php
declare(strict_types=1);
namespace Harbor\DigitalBankingLab\Application;

final class TransferValidationFailed extends \RuntimeException
{
    /** @param array<string, list<string>> $fields */
    public function __construct(public readonly array $fields) { parent::__construct('Transfer preview validation failed.'); }
}
