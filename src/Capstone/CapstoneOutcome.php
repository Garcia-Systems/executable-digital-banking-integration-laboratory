<?php
declare(strict_types=1);
namespace Harbor\DigitalBankingLab\Capstone;

enum CapstoneOutcome: string
{
    case PASS = 'PASS';
    case EXPECTED_FAILURE = 'EXPECTED_FAILURE';
    case UNEXPECTED_FAILURE = 'UNEXPECTED_FAILURE';
}
