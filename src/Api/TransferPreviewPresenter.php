<?php
declare(strict_types=1);
namespace Harbor\DigitalBankingLab\Api;
use Harbor\DigitalBankingLab\Application\TransferPreview;
use Harbor\DigitalBankingLab\Domain\Member\Money;

final readonly class TransferPreviewPresenter
{
    /** @return array<string,mixed> */
    public function present(TransferPreview $preview): array
    {
        $account = static fn($value) => ['accountId' => $value->id->value, 'displayName' => $value->displayName];
        $money = static fn(Money $value) => ['currency' => $value->currency, 'minorUnits' => $value->minorUnits, 'formatted' => $value->format()];
        return ['previewId'=>$preview->previewId,'memberId'=>$preview->memberId->value,'sourceAccount'=>$account($preview->sourceAccount),'destinationAccount'=>$account($preview->destinationAccount),'amount'=>$money($preview->amount),'sourceAvailableBalance'=>$money($preview->sourceAvailableBalance),'projectedAvailableBalance'=>$money($preview->projectedAvailableBalance),'memo'=>$preview->memo];
    }
}
