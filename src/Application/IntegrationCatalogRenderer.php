<?php

declare(strict_types=1);

namespace Harbor\DigitalBankingLab\Application;

final readonly class IntegrationCatalogRenderer
{
    public function catalog(IntegrationCatalog $catalog): string
    {
        $output = "Harbor Integration Catalog\n\n";
        foreach ($catalog->all() as $index => $item) {
            $output .= sprintf("%d. %s\n   Capability: %s\n   Adapter: %s\n   Transport: %s\n   Encoding: %s\n   Identity mapping: %s\n   Harbor result: %s\n", $index + 1, $item->externalSystem, $item->capability, $item->adapter, $item->transport, $item->encoding, $item->identityMappingRequired ? 'required' : 'not required', $item->harborResult);
        }
        return $output;
    }

    public function integration(IntegrationDescriptor $item): string
    {
        return "Integration: {$item->id}\nExternal system: {$item->externalSystem}\nCapability: {$item->capability}\nAdapter: {$item->adapter}\nTransport: {$item->transport}\nEncoding: {$item->encoding}\nIdentity mapping: " . ($item->identityMappingRequired ? 'required' : 'not required') . "\nReturns: Harbor {$item->harborResult}\n\nArchitectural path:\n{$item->requestPath}\n\nReturn:\n{$item->returnPath}\n";
    }
}
