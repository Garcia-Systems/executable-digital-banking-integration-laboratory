<?php
declare(strict_types=1);
namespace Harbor\DigitalBankingLab\Application;
use Harbor\DigitalBankingLab\Domain\Member\{MemberId, MembershipStatus};
final readonly class OperationalMember { public function __construct(public MemberId $id, public string $name, public MembershipStatus $status, public \DateTimeImmutable $createdAt) {} }
