<?php
declare(strict_types=1);
namespace Harbor\DigitalBankingLab\Integration\Heritage;

use Harbor\DigitalBankingLab\Application\AccountBalanceGateway;
use Harbor\DigitalBankingLab\Domain\Member\{AccountBalanceDetails, AccountId, AccountStatus, Money};
use Harbor\DigitalBankingLab\Integration\VendorTranslationException;
use Harbor\DigitalBankingLab\Integration\Heritage\Exception\{HeritageAccountNotFound, HeritageCoreError, HeritageResponseDecodingFailure, HeritageTransportFailure};

final readonly class HeritageCoreBankingAdapter implements AccountBalanceGateway
{
    public function __construct(private HeritageSoapClient $client, private HeritageIdentityMap $identities, private HeritageFailureTranslator $failures = new HeritageFailureTranslator()) {}
    public function accountBalanceDetails(AccountId $accountId): AccountBalanceDetails
    {
        try {
            $legacy = $this->client->getAccountDetails($this->identities->heritageAccountFor($accountId));
            if ($legacy->currencyCode !== 'USD') throw new VendorTranslationException("Unsupported Heritage CurrencyCode: {$legacy->currencyCode}");
            try { $status = AccountStatus::from($legacy->accountStatus); }
            catch (\ValueError $error) { throw new VendorTranslationException("Unsupported Heritage AccountStatus: {$legacy->accountStatus}", previous: $error); }
            return new AccountBalanceDetails($accountId, Money::usd($legacy->ledgerBalanceMinorUnits), Money::usd($legacy->availableBalanceMinorUnits), $status);
        } catch (HeritageAccountNotFound|HeritageCoreError|HeritageResponseDecodingFailure|HeritageTransportFailure|VendorTranslationException $error) {
            throw $this->failures->translate($error);
        }
    }
}
