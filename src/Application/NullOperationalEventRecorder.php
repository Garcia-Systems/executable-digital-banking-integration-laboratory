<?php
declare(strict_types=1);
namespace Harbor\DigitalBankingLab\Application;

final class NullOperationalEventRecorder implements OperationalEventRecorder
{
    public function recordIntegrationFailure(IntegrationFailureEvent $event): void {}
}
