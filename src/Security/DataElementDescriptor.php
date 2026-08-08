<?php
declare(strict_types=1);
namespace Harbor\DigitalBankingLab\Security;

/** Educational metadata only; it is not a regulatory classification. */
final readonly class DataElementDescriptor
{
    public function __construct(
        public string $name,
        public DataSensitivity $classification,
        public string $primaryUse,
        public string $publicApi,
        public string $diagnostics,
        public string $memberWeb,
    ) {}
}
