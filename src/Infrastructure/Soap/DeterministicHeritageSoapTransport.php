<?php
declare(strict_types=1);
namespace Harbor\DigitalBankingLab\Infrastructure\Soap;

/** Records requests and returns in-memory fixtures; it has no networking code. */
final class DeterministicHeritageSoapTransport implements SoapTransport
{
    /** @var list<array{endpoint:string, soapAction:string, xmlBody:string}> */
    private array $requests = [];
    public function __construct(private readonly string $scenario = 'normal') {}

    public function send(string $endpoint, string $soapAction, string $xmlBody): SoapTransportResponse
    {
        $this->requests[] = compact('endpoint', 'soapAction', 'xmlBody');
        if ($endpoint !== 'https://heritage-core.invalid/soap') throw new \InvalidArgumentException('Unexpected deterministic Heritage endpoint.');
        if ($soapAction !== 'urn:heritage-core/GetAccountDetails') throw new \InvalidArgumentException('Unexpected deterministic SOAP action.');
        if (!str_contains($xmlBody, '<her:GetAccountDetailsRequest>')) throw new \InvalidArgumentException('Unexpected deterministic SOAP request.');
        if ($this->scenario === 'malformed-xml') return new SoapTransportResponse(200, '<soap:Envelope><broken>');
        if (in_array($this->scenario, ['account-not-found', 'soap-fault', 'core-error'], true)) {
            $code = $this->scenario === 'account-not-found' ? 'ACCOUNT_NOT_FOUND' : 'CORE_ERROR';
            $message = $code === 'ACCOUNT_NOT_FOUND' ? 'Account was not found.' : 'Core processing failed.';
            return new SoapTransportResponse(200, $this->fault($code, $message));
        }
        $number = str_contains($xmlBody, 'HC-100046') ? 'HC-100046' : 'HC-100045';
        $status = $this->scenario === 'unsupported-status' ? 'DORMANT' : 'OPEN';
        $currency = $this->scenario === 'unsupported-currency' ? 'CAD' : 'USD';
        $available = $this->scenario === 'incomplete-response' ? '' : "      <her:AvailableBalanceMinorUnits>238575</her:AvailableBalanceMinorUnits>\n";
        $operation = $this->scenario === 'unexpected-operation' ? 'LookupAccountResponse' : 'GetAccountDetailsResponse';
        return new SoapTransportResponse(200, <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/" xmlns:her="urn:heritage-core">
  <soap:Body>
    <her:{$operation}>
      <her:AccountNumber>{$number}</her:AccountNumber>
      <her:AccountStatus>{$status}</her:AccountStatus>
      <her:LedgerBalanceMinorUnits>245075</her:LedgerBalanceMinorUnits>
{$available}      <her:CurrencyCode>{$currency}</her:CurrencyCode>
    </her:{$operation}>
  </soap:Body>
</soap:Envelope>
XML);
    }

    /** @return list<array{endpoint:string, soapAction:string, xmlBody:string}> */
    public function requests(): array { return $this->requests; }

    private function fault(string $code, string $message): string
    {
        return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/" xmlns:her="urn:heritage-core">
  <soap:Body><soap:Fault><faultcode>her:{$code}</faultcode><faultstring>{$message}</faultstring></soap:Fault></soap:Body>
</soap:Envelope>
XML;
    }
}
