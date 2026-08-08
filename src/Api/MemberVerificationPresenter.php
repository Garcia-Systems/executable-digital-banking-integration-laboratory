<?php
declare(strict_types=1);
namespace Harbor\DigitalBankingLab\Api;
use Harbor\DigitalBankingLab\Application\MemberVerificationResult;
final readonly class MemberVerificationPresenter { public function present(MemberVerificationResult $result):array{return ['memberId'=>$result->memberId->value,'status'=>strtolower($result->status->value)];} }
