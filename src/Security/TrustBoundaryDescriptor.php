<?php
declare(strict_types=1);
namespace Harbor\DigitalBankingLab\Security;

final readonly class TrustBoundaryDescriptor
{
    /** @param list<string> $controls */
    public function __construct(public string $identifier, public string $source, public string $destination, public string $format, public string $validationResponsibility, public DataSensitivity $sensitiveDataRisk, public array $controls) {}
}
