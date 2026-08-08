<?php

declare(strict_types=1);

namespace Harbor\DigitalBankingLab\Architecture;

/** Small, deliberately explicit Chapter 7 dependency check—not a static-analysis framework. */
final readonly class ArchitectureInspector
{
    public function __construct(private string $sourceRoot) {}

    /** @return array<string, bool> */
    public function checks(): array
    {
        $domain = $this->sources('Domain');
        $ports = $this->read('Application/DigitalBankingGateway.php') . $this->read('Application/AccountBalanceGateway.php').$this->read('Application/MemberVerificationGateway.php');
        $api = $this->sources('Api') . $this->sources('Http');
        $northstarAdapter = $this->read('Integration/Northstar/NorthstarDigitalBankingAdapter.php');
        $heritageAdapter = $this->read('Integration/Heritage/HeritageCoreBankingAdapter.php');
        $preview=$this->read('Application/PreviewTransfer.php');$verification=$this->read('Application/GetMemberVerification.php');

        return [
            'Harbor domain contains no Northstar dependency' => !str_contains($domain, 'Northstar'),
            'Harbor domain contains no Heritage dependency' => !str_contains($domain, 'Heritage'),
            'Harbor domain contains no ClearVerify dependency'=>!str_contains($domain,'ClearVerify'),
            'Harbor application ports expose Harbor-owned types' => $this->containsNone($ports, ['Guzzle', 'HttpResponse', 'array ', 'json', 'DOMDocument', 'SimpleXML', 'SoapTransport']),
            'Harbor public API contains no vendor-model dependency' => $this->containsNone($api, ['Integration\\Northstar\\Model', 'Integration\\Heritage\\Model','Integration\\ClearVerify']),
            'PreviewTransfer depends on verification port, not ClearVerify'=>str_contains($preview,'MemberVerificationGateway')&&!str_contains($preview,'ClearVerify'),
            'GetMemberVerification depends on Harbor verification port'=>str_contains($verification,'MemberVerificationGateway')&&!str_contains($verification,'ClearVerify'),
            'Northstar transport remains below adapter boundary' => str_contains($northstarAdapter, 'NorthstarClient') && !str_contains($northstarAdapter, 'HttpClient'),
            'Heritage SOAP transport remains below adapter boundary' => str_contains($heritageAdapter, 'HeritageSoapClient') && !str_contains($heritageAdapter, 'SoapTransport'),
        ];
    }

    public function render(): string
    {
        $output = "Architecture checks:\n";
        $passed = true;
        foreach ($this->checks() as $rule => $result) {
            $output .= sprintf("[%s] %s\n", $result ? 'PASS' : 'FAIL', $rule);
            $passed = $passed && $result;
        }
        return $output . "\nArchitecture: " . ($passed ? 'PASS' : 'FAIL') . "\n";
    }

    public function passes(): bool { return !in_array(false, $this->checks(), true); }

    private function read(string $relative): string
    {
        $contents = file_get_contents($this->sourceRoot . '/' . $relative);
        return $contents === false ? '' : $contents;
    }

    private function sources(string $relative): string
    {
        $source = '';
        $directory = new \RecursiveDirectoryIterator($this->sourceRoot . '/' . $relative);
        foreach (new \RecursiveIteratorIterator($directory) as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') $source .= file_get_contents($file->getPathname());
        }
        return $source;
    }

    /** @param list<string> $needles */
    private function containsNone(string $source, array $needles): bool
    {
        foreach ($needles as $needle) if (stripos($source, $needle) !== false) return false;
        return true;
    }
}
