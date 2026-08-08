<?php
declare(strict_types=1);
namespace Harbor\DigitalBankingLab\Application;
use Harbor\DigitalBankingLab\Domain\Member\MemberId;
final readonly class MemberVerificationResult { public function __construct(public MemberId $memberId,public VerificationStatus $status){} }
