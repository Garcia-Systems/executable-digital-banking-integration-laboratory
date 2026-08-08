<?php

declare(strict_types=1);

namespace Harbor\DigitalBankingLab\Fixtures;

use Harbor\DigitalBankingLab\Domain\{DigitalBankingEcosystem, DigitalSystem, IntegrationRelationship, Member, SystemCategory, SystemOwnership};

final class HarborEcosystemFixture
{
    public static function create(): DigitalBankingEcosystem
    {
        return new DigitalBankingEcosystem(
            'Harbor Community Credit Union (fictional)',
            new Member('fictional-member', 'Member'),
            [
                new DigitalSystem('member-web', 'Member Web', SystemOwnership::HARBOR, SystemCategory::CHANNEL, 'Browser experience for Harbor members.'),
                new DigitalSystem('mobile-banking', 'Mobile Banking', SystemOwnership::HARBOR, SystemCategory::CHANNEL, 'Mobile-first experience for Harbor members.'),
                new DigitalSystem('internal-operations', 'Internal Operations', SystemOwnership::HARBOR, SystemCategory::APPLICATION, 'Application used by authorized Harbor staff.'),
                new DigitalSystem('harbor-integration-layer', 'Harbor Integration Layer', SystemOwnership::HARBOR, SystemCategory::APPLICATION, 'Harbor application services and controlled integration boundary.'),
                new DigitalSystem('member-database', 'Member Database', SystemOwnership::HARBOR, SystemCategory::DATABASE, 'Laboratory store for deterministic fictional member records.'),
                new DigitalSystem('vendor-digital-banking', 'Vendor Digital Banking Platform', SystemOwnership::VENDOR, SystemCategory::VENDOR_PLATFORM, 'Vendor-owned digital banking capabilities used by Harbor.'),
                new DigitalSystem('legacy-core', 'Legacy Core Banking System', SystemOwnership::VENDOR, SystemCategory::LEGACY_SYSTEM, 'Existing vendor-owned system of record simulated by the laboratory.'),
                new DigitalSystem('fintech-provider', 'Third-Party Fintech Provider', SystemOwnership::THIRD_PARTY, SystemCategory::FINTECH_SERVICE, 'External fintech capability simulated by the laboratory.'),
            ],
            [
                new IntegrationRelationship('member-web', 'harbor-integration-layer', 'HTTPS/REST', 'Submit member web requests through Harbor-controlled services.'),
                new IntegrationRelationship('mobile-banking', 'harbor-integration-layer', 'HTTPS/REST', 'Submit mobile banking requests through Harbor-controlled services.'),
                new IntegrationRelationship('internal-operations', 'harbor-integration-layer', 'HTTPS/REST', 'Support internal operational workflows.'),
                new IntegrationRelationship('harbor-integration-layer', 'member-database', 'SQL', 'Read and persist laboratory member application data.'),
                new IntegrationRelationship('harbor-integration-layer', 'vendor-digital-banking', 'Vendor REST API', 'Use vendor-supplied digital banking capabilities.'),
                new IntegrationRelationship('harbor-integration-layer', 'legacy-core', 'SOAP', 'Exchange simulated account data with the legacy core.'),
                new IntegrationRelationship('harbor-integration-layer', 'fintech-provider', 'Fintech REST API', 'Use a simulated third-party fintech capability.'),
            ],
        );
    }
}
