<?php
declare(strict_types=1);
namespace Harbor\DigitalBankingLab\Integration\ClearVerify\Model;
final readonly class ClearVerifyResponse { public function __construct(public ClearVerifySubjectId $subjectId,public string $status,public string $reference){} }
