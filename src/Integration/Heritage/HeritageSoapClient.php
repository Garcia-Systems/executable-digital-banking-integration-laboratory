<?php
declare(strict_types=1);
namespace Harbor\DigitalBankingLab\Integration\Heritage;

use Harbor\DigitalBankingLab\Infrastructure\Soap\SoapTransport;
use Harbor\DigitalBankingLab\Integration\Heritage\Exception\{HeritageAccountNotFound, HeritageCoreError, HeritageResponseDecodingFailure, HeritageSoapFault, HeritageTransportFailure};
use Harbor\DigitalBankingLab\Integration\Heritage\Model\{HeritageAccountDetails, HeritageAccountNumber};

final readonly class HeritageSoapClient
{
    public function __construct(private SoapTransport $transport, private HeritageSoapEnvelopeBuilder $envelopes, private string $endpoint) {}

    public function getAccountDetails(HeritageAccountNumber $account): HeritageAccountDetails
    {
        $response = $this->transport->send($this->endpoint, 'urn:heritage-core/GetAccountDetails', $this->envelopes->getAccountDetails($account));
        if ($response->statusCode < 200 || $response->statusCode >= 300) throw new HeritageTransportFailure("Heritage transport returned HTTP {$response->statusCode}.");
        $previous = libxml_use_internal_errors(true);
        $document = new \DOMDocument();
        $loaded = $document->loadXML($response->xmlBody, LIBXML_NONET);
        libxml_clear_errors(); libxml_use_internal_errors($previous);
        if (!$loaded) throw new HeritageResponseDecodingFailure('Heritage response contains malformed XML.');
        $xpath = new \DOMXPath($document);
        $xpath->registerNamespace('soap', HeritageSoapEnvelopeBuilder::SOAP_NAMESPACE);
        $xpath->registerNamespace('her', HeritageSoapEnvelopeBuilder::HERITAGE_NAMESPACE);
        $fault = $xpath->query('/soap:Envelope/soap:Body/soap:Fault')->item(0);
        if ($fault instanceof \DOMElement) {
            $code = trim($xpath->evaluate('string(faultcode)', $fault));
            $message = trim($xpath->evaluate('string(faultstring)', $fault));
            $shortCode = str_contains($code, ':') ? substr($code, strpos($code, ':') + 1) : $code;
            throw match ($shortCode) {
                'ACCOUNT_NOT_FOUND' => new HeritageAccountNotFound($code, $response->statusCode, $message),
                'CORE_ERROR' => new HeritageCoreError($code, $response->statusCode, $message),
                default => new HeritageSoapFault($code, $response->statusCode, $message),
            };
        }
        $details = $xpath->query('/soap:Envelope/soap:Body/her:GetAccountDetailsResponse')->item(0);
        if (!$details instanceof \DOMElement) throw new HeritageResponseDecodingFailure('Heritage response is missing GetAccountDetailsResponse in the expected namespace.');
        $values = [];
        foreach (['AccountNumber', 'AccountStatus', 'LedgerBalanceMinorUnits', 'AvailableBalanceMinorUnits', 'CurrencyCode'] as $field) {
            $nodes = $xpath->query("her:{$field}", $details);
            if ($nodes->length !== 1 || trim($nodes->item(0)->textContent) === '') throw new HeritageResponseDecodingFailure("Heritage response field '{$field}' is required exactly once.");
            $values[$field] = trim($nodes->item(0)->textContent);
        }
        foreach (['LedgerBalanceMinorUnits', 'AvailableBalanceMinorUnits'] as $field) {
            if (preg_match('/^-?\d+$/', $values[$field]) !== 1) throw new HeritageResponseDecodingFailure("Heritage response field '{$field}' must be integer minor units.");
        }
        return new HeritageAccountDetails(new HeritageAccountNumber($values['AccountNumber']), $values['AccountStatus'], (int) $values['LedgerBalanceMinorUnits'], (int) $values['AvailableBalanceMinorUnits'], $values['CurrencyCode']);
    }
}
