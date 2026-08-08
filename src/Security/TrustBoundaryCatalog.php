<?php
declare(strict_types=1);
namespace Harbor\DigitalBankingLab\Security;

final class TrustBoundaryCatalog
{
    /** @return list<TrustBoundaryDescriptor> */
    public function all(): array
    {
        return [
            new TrustBoundaryDescriptor('browser-api','Browser','Harbor REST API','JSON / route input','request shape + Harbor value construction + application rules',DataSensitivity::MEMBER_SENSITIVE,['content type','64 KiB limit','semantic validation']),
            new TrustBoundaryDescriptor('northstar-client','Northstar','Harbor Integration','JSON','HTTP status + JSON schema expectations + enum translation',DataSensitivity::INTERNAL,['required fields','integer money','allow-listed enums']),
            new TrustBoundaryDescriptor('heritage-client','Heritage','Harbor Integration','SOAP/XML','safe XML parse + SOAP fault + required fields',DataSensitivity::MEMBER_SENSITIVE,['LIBXML_NONET','DOCTYPE rejection','256 KiB limit']),
            new TrustBoundaryDescriptor('clearverify-client','ClearVerify','Harbor Integration','JSON','response shape + subject match + status allow list',DataSensitivity::INTERNAL,['required fields','allow-listed status translation']),
            new TrustBoundaryDescriptor('database-repository','Harbor Database','SQL Repository','rows / scalars','row mapping + enum validation + expected types',DataSensitivity::MEMBER_SENSITIVE,['bound parameters','typed Harbor mapping']),
            new TrustBoundaryDescriptor('application-web','Harbor Application','Member Web','Harbor-owned JSON','intentional presenter + safe string rendering + no vendor leakage',DataSensitivity::MEMBER_SENSITIVE,['runtime DTO validation','HTML escaping','text-only fields']),
            new TrustBoundaryDescriptor('configuration-infrastructure','Configuration / environment','Harbor Infrastructure','strings','explicit configuration validation + secret minimization',DataSensitivity::SECRET,['no public output','no raw diagnostic fields']),
        ];
    }

    public function render(): string
    {
        $output="Harbor Trust Boundaries\n\n";
        foreach($this->all() as $i=>$boundary) $output.=($i+1).". {$boundary->source} → {$boundary->destination}\n   Format: {$boundary->format}\n   Validation: {$boundary->validationResponsibility}\n";
        return $output;
    }
}
