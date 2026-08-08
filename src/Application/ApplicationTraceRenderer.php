<?php
declare(strict_types=1);
namespace Harbor\DigitalBankingLab\Application;

final readonly class ApplicationTraceRenderer
{
    public function render(ApplicationTrace $trace): string
    {
        $output = "Application service: GetMemberFinancialOverview\n\n";
        foreach ($trace->steps() as $index => $step) $output .= ($index + 1) . ". {$step}\n";
        return $output;
    }
}
