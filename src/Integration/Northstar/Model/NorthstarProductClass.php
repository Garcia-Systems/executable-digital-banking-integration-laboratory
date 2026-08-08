<?php
declare(strict_types=1);
namespace Harbor\DigitalBankingLab\Integration\Northstar\Model;
final readonly class NorthstarProductClass
{
    public const DDA = 'DDA';
    public const SAV = 'SAV';
    public function __construct(public string $value) { if ($value === '') throw new \InvalidArgumentException('Northstar product class must not be empty.'); }
}
