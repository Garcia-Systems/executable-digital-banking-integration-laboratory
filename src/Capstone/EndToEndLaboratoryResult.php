<?php
declare(strict_types=1);
namespace Harbor\DigitalBankingLab\Capstone;

use Harbor\DigitalBankingLab\Application\{MemberActivityProfile, MemberFinancialOverview, MemberVerificationResult, TransferPreview};
use Harbor\DigitalBankingLab\Domain\Member\Member;

/** High-level Harbor outcomes only: no transport payloads, SQL rows, or vendor identities. */
final readonly class EndToEndLaboratoryResult
{
    /** @param list<string> $safeAnalyticsEvents */
    public function __construct(
        public string $scenario,
        public CapstoneOutcome $outcome,
        public ?Member $member,
        public ?MemberFinancialOverview $financialOverview,
        public ?MemberActivityProfile $activityProfile,
        public ?MemberVerificationResult $verification,
        public ?TransferPreview $transferPreview,
        public array $safeAnalyticsEvents,
        public string $safeFailure = '',
    ) {}
}
