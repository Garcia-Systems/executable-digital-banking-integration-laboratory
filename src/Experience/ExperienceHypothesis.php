<?php
declare(strict_types=1);
namespace Harbor\DigitalBankingLab\Experience;
final readonly class ExperienceHypothesis { public function __construct(public string $observation,public string $hypothesis,public string $proposedChange,public string $metric,public string $guardrail){} }
