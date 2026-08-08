<?php
declare(strict_types=1);
namespace Harbor\DigitalBankingLab\Application;

interface OperationalEventRecorder { public function recordIntegrationFailure(IntegrationFailureEvent $event): void; }
