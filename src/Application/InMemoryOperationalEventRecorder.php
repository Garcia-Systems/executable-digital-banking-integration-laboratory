<?php
declare(strict_types=1);
namespace Harbor\DigitalBankingLab\Application;

/** Deterministic laboratory recorder; production retention and logging are out of scope. */
final class InMemoryOperationalEventRecorder implements OperationalEventRecorder
{
    /** @var list<IntegrationFailureEvent> */
    private array $events=[];
    public function recordIntegrationFailure(IntegrationFailureEvent $event): void { $this->events[]=$event; }
    /** @return list<IntegrationFailureEvent> */
    public function events(): array { return $this->events; }
    public function render(): string { return implode("\n",array_map(static fn(IntegrationFailureEvent $event):string=>$event->render(),$this->events)); }
}
