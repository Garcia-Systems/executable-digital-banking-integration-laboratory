<?php
declare(strict_types=1);
namespace Harbor\DigitalBankingLab\Application;

enum RetryDisposition: string
{
    case RETRYABLE = 'RETRYABLE';
    case NOT_RETRYABLE = 'NOT_RETRYABLE';
    case UNKNOWN = 'UNKNOWN';
}
