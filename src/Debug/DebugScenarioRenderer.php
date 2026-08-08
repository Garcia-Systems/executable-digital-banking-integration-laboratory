<?php

declare(strict_types=1);

namespace Harbor\DigitalBankingLab\Debug;

final class DebugScenarioRenderer
{
    public function catalog(DebugScenarioCatalog $catalog): string
    {
        $out="Full-Stack Debug Scenarios\n\n";
        foreach($catalog->all() as $s) $out.="{$s->id}\nSymptom: {$s->symptom}\nJourney: {$s->journey}\n\n";
        return $out;
    }
    public function run(DebugScenario $s): string
    {
        return "Scenario: {$s->id}\n\nObserved request:\n{$s->request}\n\nObserved response:\n{$s->response}\n\nDiagnostic summary:\nA controlled {$s->journey} failure was recorded.\n";
    }
    public function trace(DebugScenario $s): string
    {
        return "Scenario: {$s->id}\n\nRequest path\n\n".implode("\n",$s->trace)."\n\nFirst failing boundary:\n{$s->firstDivergence}\n";
    }
    public function detail(DebugScenario $s): string
    {
        $out="Scenario: {$s->id}\n\nBoundary:\n{$s->firstDivergence}\n\n";
        foreach($s->detail as $key=>$value) $out.="{$key}:\n{$value}\n\n";
        return $out."Regression guard:\n{$s->regressionTest}\n";
    }
}
