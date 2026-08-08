<?php

declare(strict_types=1);

namespace Harbor\DigitalBankingLab\Domain;

final readonly class DigitalBankingEcosystem
{
    /** @param list<DigitalSystem> $systems @param list<IntegrationRelationship> $relationships */
    public function __construct(
        public string $institutionName,
        public Member $member,
        public array $systems,
        public array $relationships,
    ) {
        $ids = [];
        foreach ($systems as $system) {
            if (isset($ids[$system->identifier])) {
                throw new \InvalidArgumentException("Duplicate system: {$system->identifier}");
            }
            $ids[$system->identifier] = true;
        }
        foreach ($relationships as $relationship) {
            if (!isset($ids[$relationship->sourceSystemId], $ids[$relationship->destinationSystemId])) {
                throw new \InvalidArgumentException('Every relationship must reference known systems.');
            }
        }
    }

    public function system(string $identifier): DigitalSystem
    {
        foreach ($this->systems as $system) {
            if ($system->identifier === $identifier) {
                return $system;
            }
        }
        throw new \InvalidArgumentException("Unknown system: {$identifier}");
    }

    /** @return list<DigitalSystem> */
    public function path(string $sourceId, string $destinationId): array
    {
        $this->system($sourceId);
        $this->system($destinationId);
        $queue = [[$sourceId]];
        $visited = [$sourceId => true];
        while ($queue !== []) {
            $path = array_shift($queue);
            $current = $path[array_key_last($path)];
            if ($current === $destinationId) {
                return array_map(fn (string $id): DigitalSystem => $this->system($id), $path);
            }
            foreach ($this->relationships as $relationship) {
                if ($relationship->sourceSystemId === $current && !isset($visited[$relationship->destinationSystemId])) {
                    $visited[$relationship->destinationSystemId] = true;
                    $queue[] = [...$path, $relationship->destinationSystemId];
                }
            }
        }
        throw new \RuntimeException("No integration path from {$sourceId} to {$destinationId}.");
    }
}
