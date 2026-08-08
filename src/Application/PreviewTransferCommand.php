<?php
declare(strict_types=1);
namespace Harbor\DigitalBankingLab\Application;

use Harbor\DigitalBankingLab\Domain\Member\{AccountId, MemberId, Money};

final readonly class PreviewTransferCommand
{
    public function __construct(
        public MemberId $memberId,
        public AccountId $sourceAccountId,
        public AccountId $destinationAccountId,
        public Money $amount,
        public ?string $memo,
    ) {}
}
