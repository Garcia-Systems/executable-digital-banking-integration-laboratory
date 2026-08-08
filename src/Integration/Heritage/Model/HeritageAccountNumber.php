<?php
declare(strict_types=1);
namespace Harbor\DigitalBankingLab\Integration\Heritage\Model;

final readonly class HeritageAccountNumber
{
    public function __construct(public string $value)
    {
        if ($value === '') throw new \InvalidArgumentException('Heritage account number must not be empty.');
    }
}
