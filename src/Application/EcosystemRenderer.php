<?php

declare(strict_types=1);

namespace Harbor\DigitalBankingLab\Application;

use Harbor\DigitalBankingLab\Domain\{DigitalBankingEcosystem, SystemOwnership};

final class EcosystemRenderer
{
    public function render(DigitalBankingEcosystem $ecosystem): string
    {
        $lines = ["{$ecosystem->institutionName} — Digital Banking Integration Ecosystem", "Actor: {$ecosystem->member->displayName} (fictional)", ''];
        foreach ([SystemOwnership::HARBOR, SystemOwnership::VENDOR, SystemOwnership::THIRD_PARTY] as $ownership) {
            $lines[] = "{$ownership->value}-OWNED SYSTEMS";
            foreach ($ecosystem->systems as $system) {
                if ($system->ownership === $ownership) {
                    $lines[] = "- {$system->displayName} [{$system->identifier}] ({$system->category->value}): {$system->description}";
                }
            }
            $lines[] = '';
        }
        $lines[] = 'INTEGRATION RELATIONSHIPS';
        foreach ($ecosystem->relationships as $relationship) {
            $source = $ecosystem->system($relationship->sourceSystemId)->displayName;
            $destination = $ecosystem->system($relationship->destinationSystemId)->displayName;
            $lines[] = "- {$source} -> {$destination} [{$relationship->interactionType}]: {$relationship->purpose}";
        }
        return implode("\n", $lines) . "\n";
    }

    public function renderMemberWebPath(DigitalBankingEcosystem $ecosystem): string
    {
        $path = $ecosystem->path('member-web', 'vendor-digital-banking');
        return "Member Web integration path\n" . implode("\n  -> ", array_map(fn ($system) => $system->displayName . " [{$system->ownership->value}]", $path)) . "\n";
    }
}
