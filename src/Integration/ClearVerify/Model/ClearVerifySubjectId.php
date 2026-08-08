<?php
declare(strict_types=1);
namespace Harbor\DigitalBankingLab\Integration\ClearVerify\Model;
final readonly class ClearVerifySubjectId { public function __construct(public string $value){if(!preg_match('/^CV-SUBJECT-[0-9]+$/',$value))throw new \InvalidArgumentException('Invalid ClearVerify subject ID.');} }
