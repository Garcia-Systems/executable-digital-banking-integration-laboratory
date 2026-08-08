<?php
declare(strict_types=1);
namespace Harbor\DigitalBankingLab\Application;

use Harbor\DigitalBankingLab\Domain\Member\{Member, MemberId};
use Harbor\DigitalBankingLab\Integration\UnknownVendorIdentity;

/** Normalizes a laboratory identity-map miss into Harbor's existing member outcome. */
final readonly class MemberNotFoundGateway implements DigitalBankingGateway
{
    public function __construct(private DigitalBankingGateway $gateway) {}
    public function findMember(MemberId $memberId): Member
    {
        try { return $this->gateway->findMember($memberId); }
        catch (UnknownVendorIdentity) { throw new MemberNotFound('Member was not found.'); }
    }
}
