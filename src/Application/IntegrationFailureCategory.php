<?php
declare(strict_types=1);
namespace Harbor\DigitalBankingLab\Application;

enum IntegrationFailureCategory: string
{
    case NOT_FOUND = 'NOT_FOUND';
    case TEMPORARY_UNAVAILABLE = 'TEMPORARY_UNAVAILABLE';
    case TIMEOUT = 'TIMEOUT';
    case EXTERNAL_ERROR = 'EXTERNAL_ERROR';
    case INVALID_EXTERNAL_RESPONSE = 'INVALID_EXTERNAL_RESPONSE';
    case UNSUPPORTED_EXTERNAL_VALUE = 'UNSUPPORTED_EXTERNAL_VALUE';
}
