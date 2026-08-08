<?php
declare(strict_types=1);
namespace Harbor\DigitalBankingLab\Application;

use Harbor\DigitalBankingLab\Domain\IdGenerator;
use Harbor\DigitalBankingLab\Domain\Member\{Account, AccountStatus};

final readonly class PreviewTransfer
{
    private MemberVerificationGateway $verification;
    public function __construct(
        private DigitalBankingGateway $digitalBanking,
        private AccountBalanceGateway $balances,
        private IdGenerator $ids,
        ?MemberVerificationGateway $verification=null,
    ) { $this->verification=$verification??new AlwaysVerifiedMemberGateway(); }

    public function execute(PreviewTransferCommand $command): TransferPreview
    {
        $member = $this->digitalBanking->findMember($command->memberId);
        $source = $this->account($member->accounts, $command->sourceAccountId->value, 'sourceAccountId', 'Source');
        $destination = $this->account($member->accounts, $command->destinationAccountId->value, 'destinationAccountId', 'Destination');
        if ($source->id->equals($destination->id)) throw new TransferValidationFailed(['destinationAccountId' => ['Source and destination accounts must be different.']]);
        if ($source->status !== AccountStatus::OPEN) throw new TransferValidationFailed(['sourceAccountId' => ['Source account must be open.']]);
        if ($destination->status !== AccountStatus::OPEN) throw new TransferValidationFailed(['destinationAccountId' => ['Destination account must be open.']]);
        if ($command->amount->minorUnits <= 0) throw new TransferValidationFailed(['amount.minorUnits' => ['Amount must be greater than zero.']]);
        $verification = $this->verification->verificationFor($member->id);
        if ($verification->status === VerificationStatus::REVIEW_REQUIRED) throw new TransferPreviewBlocked(TransferPreviewBlockReason::VERIFICATION_REVIEW_REQUIRED);
        if ($verification->status === VerificationStatus::NOT_VERIFIED) throw new TransferPreviewBlocked(TransferPreviewBlockReason::VERIFICATION_REQUIRED);
        $available = $this->balances->accountBalanceDetails($source->id)->availableBalance;
        if ($available->currency !== $command->amount->currency) throw new TransferValidationFailed(['amount.currency' => ['Amount currency must match the account currency.']]);
        if ($command->amount->minorUnits > $available->minorUnits) throw new TransferValidationFailed(['amount.minorUnits' => ["Amount exceeds the source account's available balance."]]);
        return new TransferPreview($this->ids->nextId(), $member->id, $source, $destination, $command->amount, $command->memo, $available, $available->subtract($command->amount));
    }

    /** @param list<Account> $accounts */
    private function account(array $accounts, string $id, string $field, string $label): Account
    {
        foreach ($accounts as $account) if ($account->id->value === $id) return $account;
        throw new TransferValidationFailed([$field => ["{$label} account does not belong to this member."]]);
    }
}
