<?php
declare(strict_types=1);
namespace Harbor\DigitalBankingLab\Integration\ClearVerify;
use Harbor\DigitalBankingLab\Domain\Member\MemberId;
use Harbor\DigitalBankingLab\Integration\ClearVerify\Model\ClearVerifySubjectId;
final readonly class ClearVerifyIdentityMap {
 /** @param array<string,string> $subjects */ private function __construct(private array $subjects){}
 public static function laboratory():self{return new self(['member-0001'=>'CV-SUBJECT-7101','member-0002'=>'CV-SUBJECT-7102','member-0003'=>'CV-SUBJECT-7103']);}
 public function subjectFor(MemberId $id):ClearVerifySubjectId{return new ClearVerifySubjectId($this->subjects[$id->value]??throw new \InvalidArgumentException("No ClearVerify identity for {$id->value}."));}
}
