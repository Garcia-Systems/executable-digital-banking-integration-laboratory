<?php
declare(strict_types=1);
namespace Harbor\DigitalBankingLab\Integration\Northstar\Model;
final readonly class NorthstarCustomerKey { public function __construct(public string $value) { if ($value === '') throw new \InvalidArgumentException('Northstar customer key must not be empty.'); } }
