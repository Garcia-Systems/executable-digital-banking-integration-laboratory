<?php
declare(strict_types=1);
namespace Harbor\DigitalBankingLab\Delivery;

/** A compact, immutable pull-request-shaped teaching record; it never creates a real pull request. */
final readonly class ChangeReview
{
    /** @param list<string> $affectedAreas @param list<string> $requiredValidation @param list<string> $reviewerQuestions */
    public function __construct(
        public string $title,
        public string $changeSummary,
        public array $affectedAreas,
        public array $requiredValidation,
        public array $reviewerQuestions,
        public DeploymentReadinessResult $readiness,
    ) {}
}
