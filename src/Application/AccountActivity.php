<?php
declare(strict_types=1);
namespace Harbor\DigitalBankingLab\Application;
use Harbor\DigitalBankingLab\Domain\Member\{AccountId, Money};
final readonly class AccountActivity { public function __construct(public string $id, public AccountId $accountId, public string $type, public \DateTimeImmutable $occurredAt, public ?Money $amount) {} }
