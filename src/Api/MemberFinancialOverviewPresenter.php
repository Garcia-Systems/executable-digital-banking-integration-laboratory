<?php
declare(strict_types=1);
namespace Harbor\DigitalBankingLab\Api;

use Harbor\DigitalBankingLab\Application\MemberFinancialOverview;

/** An intentional public projection; application/domain objects are never serialized blindly. */
final readonly class MemberFinancialOverviewPresenter
{
    /** @return array<string, mixed> */
    public function present(MemberFinancialOverview $overview): array
    {
        $member = $overview->member;
        return [
            'memberId' => $member->id->value, 'name' => $member->name, 'status' => strtolower($member->status->value),
            'accounts' => array_map(static fn ($item): array => [
                'accountId' => $item->account->id->value,
                'displayName' => $item->account->displayName,
                'type' => strtolower($item->account->type->value),
                'digitalBankingBalance' => self::money($item->account->balance),
                'ledgerBalance' => self::money($item->ledgerBalance),
                'availableBalance' => self::money($item->availableBalance),
                'status' => strtolower($item->account->status->value),
            ], $overview->accounts),
        ];
    }

    /** @return array{currency:string,minorUnits:int,formatted:string} */
    private static function money(object $money): array
    {
        return ['currency' => $money->currency, 'minorUnits' => $money->minorUnits, 'formatted' => $money->format()];
    }
}
