<?php
declare(strict_types=1);
namespace Harbor\DigitalBankingLab\Application;

use Harbor\DigitalBankingLab\Domain\Member\MemberId;

/** Coordinates Harbor capabilities sequentially; transports and vendors remain below the ports. */
final readonly class GetMemberFinancialOverview
{
    public function __construct(
        private DigitalBankingGateway $digitalBanking,
        private AccountBalanceGateway $balances,
        private ?ApplicationTrace $trace = null,
    ) {}

    public function execute(MemberId $memberId): MemberFinancialOverview
    {
        $this->trace?->record("DigitalBankingGateway.getMember({$memberId->value})");
        $member = $this->digitalBanking->findMember($memberId);
        $this->trace?->record('Returned Member with ' . count($member->accounts) . ' accounts');
        $accounts = [];
        foreach ($member->accounts as $account) {
            $this->trace?->record("AccountBalanceGateway.getDetails({$account->id->value})");
            $details = $this->balances->accountBalanceDetails($account->id);
            if ($details->accountId->value !== $account->id->value) {
                throw new \UnexpectedValueException("Balance details identity does not match requested Harbor account {$account->id->value}.");
            }
            $accounts[] = new AccountFinancialOverview($account, $details->ledgerBalance, $details->availableBalance);
        }
        $this->trace?->record('Construct MemberFinancialOverview');
        $result = new MemberFinancialOverview($member, $accounts);
        $this->trace?->record('Return application result');
        return $result;
    }
}
