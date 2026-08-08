<?php
declare(strict_types=1);
namespace Harbor\DigitalBankingLab\Security;

final readonly class SensitiveDataFlow
{
    /** @param list<array{component:string,representation:string}> $steps */
    public function __construct(public string $id, public string $title, public array $steps) {}
}
