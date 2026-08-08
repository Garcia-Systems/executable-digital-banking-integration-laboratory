<?php
declare(strict_types=1);
namespace Harbor\DigitalBankingLab\Integration\Heritage;

use Harbor\DigitalBankingLab\Integration\Heritage\Model\HeritageAccountNumber;

final class HeritageSoapEnvelopeBuilder
{
    public const SOAP_NAMESPACE = 'http://schemas.xmlsoap.org/soap/envelope/';
    public const HERITAGE_NAMESPACE = 'urn:heritage-core';

    public function getAccountDetails(HeritageAccountNumber $account): string
    {
        $escaped = htmlspecialchars($account->value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
        return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/" xmlns:her="urn:heritage-core">
  <soap:Body>
    <her:GetAccountDetailsRequest>
      <her:AccountNumber>{$escaped}</her:AccountNumber>
    </her:GetAccountDetailsRequest>
  </soap:Body>
</soap:Envelope>
XML . "\n";
    }
}
