<?php
declare(strict_types=1);
namespace Harbor\DigitalBankingLab\Application;
use Harbor\DigitalBankingLab\Domain\Member\{AccountId, AccountStatus, AccountType, MemberId};
final readonly class OperationalAccount { public function __construct(public AccountId $id, public MemberId $memberId, public string $name, public AccountType $type, public AccountStatus $status) {} }
