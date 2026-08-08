<?php
declare(strict_types=1);
namespace Harbor\DigitalBankingLab\Delivery;

final readonly class DeploymentReadinessResult { public function __construct(public array $gates,public array $findings){} public function ready():bool{return $this->findings===[];} }
final class DeploymentReadinessCheck
{
    /** @param array<string,bool> $overrides */
    public function evaluate(array $overrides=[]):DeploymentReadinessResult
    {
        $gates=array_replace(['Git working tree'=>true,'Branch known'=>true,'Unit tests'=>true,'Integration tests'=>true,'Frontend tests'=>true,'Architecture'=>true,'Security'=>true,'API data exposure'=>true,'Contract compatibility'=>true,'Documentation'=>true,'Database migration'=>true,'Configuration safety'=>true],$overrides);
        $findings=[];foreach($gates as $gate=>$pass)if(!$pass)$findings[]="{$gate} did not pass.";
        return new DeploymentReadinessResult($gates,$findings);
    }
    public static function scenario(string $id):array {return match($id){'dirty-working-tree'=>['Git working tree'=>false],'failing-unit-test'=>['Unit tests'=>false],'api-contract-mismatch'=>['Contract compatibility'=>false],'vendor-id-exposure'=>['API data exposure'=>false,'Security'=>false],'missing-migration'=>['Database migration'=>false],'documentation-only-typo'=>[],default=>throw new \InvalidArgumentException("Unknown readiness scenario: {$id}")};}
}
