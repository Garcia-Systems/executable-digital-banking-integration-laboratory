<?php
declare(strict_types=1);
namespace Harbor\DigitalBankingLab\Application;

use Harbor\DigitalBankingLab\Domain\Member\{Account, MemberId, Money};

final readonly class TransferPreview
{
    public function __construct(
        public string $previewId,
        public MemberId $memberId,
        public Account $sourceAccount,
        public Account $destinationAccount,
        public Money $amount,
        public ?string $memo,
        public Money $sourceAvailableBalance,
        public Money $projectedAvailableBalance,
    ) {}
}
