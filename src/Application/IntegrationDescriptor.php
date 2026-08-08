<?php

declare(strict_types=1);

namespace Harbor\DigitalBankingLab\Application;

/** Immutable educational metadata. Runtime integration wiring does not depend on it. */
final readonly class IntegrationDescriptor
{
    public function __construct(
        public string $id,
        public string $externalSystem,
        public string $capability,
        public string $port,
        public string $adapter,
        public string $client,
        public string $transport,
        public string $encoding,
        public bool $identityMappingRequired,
        public string $harborResult,
        public string $requestPath,
        public string $returnPath,
    ) {
        if ($id === '' || preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $id) !== 1) {
            throw new \InvalidArgumentException('Integration identifiers must be stable kebab-case values.');
        }
    }
}
