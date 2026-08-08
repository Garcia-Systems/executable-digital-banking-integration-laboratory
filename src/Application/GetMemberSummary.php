<?php

declare(strict_types=1);

namespace Harbor\DigitalBankingLab\Application;

use Harbor\DigitalBankingLab\Domain\Member\{Member, MemberId};
use Harbor\DigitalBankingLab\Integration\UnknownVendorIdentity;

/** Transport-independent application capability used by HTTP, CLI, or tests. */
final readonly class GetMemberSummary
{
    public function __construct(private DigitalBankingGateway $gateway)
    {
    }

    public function execute(MemberId $memberId): Member
    {
        try {
            return $this->gateway->findMember($memberId);
        } catch (UnknownVendorIdentity) {
            throw new MemberNotFound('Member was not found.');
        }
    }
}
