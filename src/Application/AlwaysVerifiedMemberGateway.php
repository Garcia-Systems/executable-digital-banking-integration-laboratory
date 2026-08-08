<?php
declare(strict_types=1);
namespace Harbor\DigitalBankingLab\Application;
use Harbor\DigitalBankingLab\Domain\Member\MemberId;
/** Chapter 14 compatibility default; production composition always supplies the Chapter 15 gateway. */
final readonly class AlwaysVerifiedMemberGateway implements MemberVerificationGateway { public function verificationFor(MemberId $id):MemberVerificationResult{return new MemberVerificationResult($id,VerificationStatus::VERIFIED);} }
