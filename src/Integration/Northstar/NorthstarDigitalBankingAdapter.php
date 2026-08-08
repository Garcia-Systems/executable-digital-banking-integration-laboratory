<?php
declare(strict_types=1);
namespace Harbor\DigitalBankingLab\Integration\Northstar;

use Harbor\DigitalBankingLab\Application\DigitalBankingGateway;
use Harbor\DigitalBankingLab\Domain\Member\{Account, Member, MemberId, Money};
use Harbor\DigitalBankingLab\Integration\VendorIdentityMap;
use Harbor\DigitalBankingLab\Integration\VendorTranslationException;
use Harbor\DigitalBankingLab\Integration\Northstar\Exception\{NorthstarCustomerNotFound, NorthstarHttpFailure, NorthstarResponseDecodingFailure, NorthstarTransportFailure};

final readonly class NorthstarDigitalBankingAdapter implements DigitalBankingGateway
{
    public function __construct(private NorthstarClient $client, private VendorIdentityMap $identities, private NorthstarTranslator $translator, private NorthstarFailureTranslator $failures = new NorthstarFailureTranslator()) {}

    public function findMember(MemberId $memberId): Member
    {
        try {
            $customer = $this->client->findCustomer($this->identities->northstarCustomerFor($memberId));
            $accounts = [];
            foreach ($customer->products as $product) {
                $accounts[] = new Account(
                    $this->identities->harborAccountFor($product->productKey),
                    $this->translator->accountType($product->productClass),
                    $product->nickname,
                    Money::usd($product->currentBalanceCents),
                    $this->translator->accountStatus($product->state),
                );
            }
        } catch (NorthstarCustomerNotFound|NorthstarHttpFailure|NorthstarResponseDecodingFailure|NorthstarTransportFailure|VendorTranslationException $error) {
            throw $this->failures->translate($error);
        }
        return new Member($memberId, $customer->fullName, $this->translator->membershipStatus($customer->customerStatus), $accounts);
    }
}
