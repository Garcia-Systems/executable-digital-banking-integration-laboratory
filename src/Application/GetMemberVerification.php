<?php
declare(strict_types=1);
namespace Harbor\DigitalBankingLab\Application;
use Harbor\DigitalBankingLab\Domain\Member\MemberId;
final readonly class GetMemberVerification { public function __construct(private MemberVerificationGateway $gateway){} public function execute(MemberId $id):MemberVerificationResult{return $this->gateway->verificationFor($id);} }
