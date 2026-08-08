<?php
declare(strict_types=1);
namespace Harbor\DigitalBankingLab\Application;
use Harbor\DigitalBankingLab\Domain\Member\MemberId;
final readonly class InactiveMemberSummary { public function __construct(public MemberId $id, public string $name) {} }
