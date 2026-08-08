<?php
declare(strict_types=1);
namespace Harbor\DigitalBankingLab\Experience;
final readonly class FunnelResult {
    /** @param array<string,int> $steps */
    public function __construct(public array $steps){}
    public function totalConversion():float{$values=array_values($this->steps);return self::percentage($values[count($values)-1]??0,$values[0]??0);}
    /** @return array<string,int> */ public function abandonment():array{$out=[];$keys=array_keys($this->steps);for($i=1;$i<count($keys);$i++)$out[$keys[$i-1].' → '.$keys[$i]]=$this->steps[$keys[$i-1]]-$this->steps[$keys[$i]];return $out;}
    /** @return array<string,float> */ public function stepConversion():array{$out=[];$keys=array_keys($this->steps);for($i=1;$i<count($keys);$i++)$out[$keys[$i]]=self::percentage($this->steps[$keys[$i]],$this->steps[$keys[$i-1]]);return $out;}
    public static function percentage(int $part,int $whole):float{return $whole===0?0.0:round($part/$whole*100,1,PHP_ROUND_HALF_UP);}
}
