<?php
declare(strict_types=1);
namespace Harbor\DigitalBankingLab\Integration\ClearVerify;
use Harbor\DigitalBankingLab\Application\{IntegrationFailure,IntegrationFailureCategory,IntegrationOperation,MemberVerificationGateway,MemberVerificationResult,RetryDisposition,VerificationStatus};
use Harbor\DigitalBankingLab\Domain\Member\MemberId;
use Harbor\DigitalBankingLab\Integration\ClearVerify\Model\ClearVerifyStatus;
final readonly class ClearVerifyMemberVerificationAdapter implements MemberVerificationGateway {
 public function __construct(private ClearVerifyRestClient $client,private ClearVerifyIdentityMap $identities){}
 public function verificationFor(MemberId $memberId):MemberVerificationResult {
  $response=$this->client->status($this->identities->subjectFor($memberId));$vendor=ClearVerifyStatus::tryFrom($response->status);
  if($vendor===null)throw new IntegrationFailure(IntegrationFailureCategory::UNSUPPORTED_EXTERNAL_VALUE,RetryDisposition::NOT_RETRYABLE,'Member verification returned an unsupported value.','ClearVerify Identity Services',IntegrationOperation::MEMBER_VERIFICATION,'CLEARVERIFY_UNSUPPORTED_STATUS');
  $status=match($vendor){ClearVerifyStatus::PASS=>VerificationStatus::VERIFIED,ClearVerifyStatus::MANUAL_REVIEW=>VerificationStatus::REVIEW_REQUIRED,ClearVerifyStatus::FAIL=>VerificationStatus::NOT_VERIFIED};
  return new MemberVerificationResult($memberId,$status);
 }
}
