<?php
declare(strict_types=1);
namespace Harbor\DigitalBankingLab\Integration\Northstar;

use Harbor\DigitalBankingLab\Application\DigitalBankingGateway;
use Harbor\DigitalBankingLab\Domain\Member\{Account, Member, MemberId, Money};
use Harbor\DigitalBankingLab\Integration\VendorIdentityMap;

final readonly class NorthstarDigitalBankingAdapter implements DigitalBankingGateway
{
    public function __construct(private NorthstarClient $client, private VendorIdentityMap $identities, private NorthstarTranslator $translator) {}

    public function findMember(MemberId $memberId): Member
    {
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
        return new Member($memberId, $customer->fullName, $this->translator->membershipStatus($customer->customerStatus), $accounts);
    }
}
