<?php

declare(strict_types=1);

namespace Harbor\DigitalBankingLab\Application;

final readonly class IntegrationCatalog
{
    /** @return list<IntegrationDescriptor> */
    public function all(): array
    {
        $integrations = [
            new IntegrationDescriptor(
                'northstar-digital-banking', 'Northstar Digital Banking', 'Member lookup',
                'DigitalBankingGateway', 'NorthstarDigitalBankingAdapter',
                'NorthstarClient → NorthstarRestClient', 'REST / HTTP', 'JSON', true, 'Member',
                'Harbor Capability → DigitalBankingGateway → NorthstarDigitalBankingAdapter → NorthstarClient → NorthstarRestClient → HTTP → Northstar Digital Banking',
                'Northstar JSON → Northstar model → Adapter translation → Harbor Member',
            ),
            new IntegrationDescriptor(
                'heritage-core-banking', 'Heritage Core Banking', 'Account balance details',
                'AccountBalanceGateway', 'HeritageCoreBankingAdapter', 'HeritageSoapClient',
                'SOAP', 'XML', true, 'AccountBalanceDetails',
                'Harbor Capability → AccountBalanceGateway → HeritageCoreBankingAdapter → HeritageSoapClient → SOAP/XML → Heritage Core Banking',
                'SOAP XML → Heritage model → Adapter translation → Harbor AccountBalanceDetails',
            ),
            new IntegrationDescriptor(
                'clearverify-identity-services','ClearVerify Identity Services','Member verification',
                'MemberVerificationGateway','ClearVerifyMemberVerificationAdapter','ClearVerifyRestClient',
                'REST / HTTP','JSON',true,'MemberVerificationResult',
                'Harbor Capability → MemberVerificationGateway → ClearVerifyMemberVerificationAdapter → ClearVerifyRestClient → REST/JSON → ClearVerify',
                'ClearVerify response → Adapter translation → Harbor MemberVerificationResult',
            ),
        ];
        usort($integrations,fn(IntegrationDescriptor $a,IntegrationDescriptor $b)=>$a->id<=>$b->id);
        return $integrations;
    }

    public function find(string $id): ?IntegrationDescriptor
    {
        foreach ($this->all() as $integration) {
            if ($integration->id === $id) return $integration;
        }
        return null;
    }
}
