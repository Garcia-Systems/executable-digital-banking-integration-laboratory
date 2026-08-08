<?php
declare(strict_types=1);
namespace Harbor\DigitalBankingLab\Application;
use Harbor\DigitalBankingLab\Domain\Member\MemberId;
interface MemberVerificationGateway { public function verificationFor(MemberId $memberId):MemberVerificationResult; }
