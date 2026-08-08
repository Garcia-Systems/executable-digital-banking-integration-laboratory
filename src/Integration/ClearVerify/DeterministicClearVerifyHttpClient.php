<?php
declare(strict_types=1);
namespace Harbor\DigitalBankingLab\Integration\ClearVerify;
use Harbor\DigitalBankingLab\Infrastructure\Http\{HttpClient,HttpResponse,HttpTimeoutException,HttpTransportException};

/** Records requests and reads fixtures only; it has no networking implementation. */
final class DeterministicClearVerifyHttpClient implements HttpClient {
    private array $requests=[];
    public function __construct(private readonly string $scenario,private readonly string $fixtures){}
    public function request(string $method,string $url,array $headers=[]):HttpResponse {
        $this->requests[]=compact('method','url','headers');
        if($this->scenario==='verification-timeout')throw new HttpTimeoutException('Simulated instant timeout.');
        if($this->scenario==='verification-unavailable')throw new HttpTransportException('Simulated unavailable service.');
        [$file,$status]=match($this->scenario){
            'verification-pass'=>['pass.json',200], 'verification-review'=>['review.json',200], 'verification-fail'=>['fail.json',200],
            'verification-http-error'=>['pass.json',500], 'verification-malformed-json'=>['malformed.json',200],
            'verification-incomplete-response'=>['incomplete.json',200], 'verification-unsupported-status'=>['unsupported.json',200],
            default=>throw new \InvalidArgumentException("Unknown ClearVerify scenario: {$this->scenario}"),
        };
        $body=(string)file_get_contents($this->fixtures.'/'.$file);
        if(in_array($this->scenario,['verification-pass','verification-review','verification-fail'],true)&&preg_match('#/verification-subjects/([^/]+)/status$#',$url,$matches)){
            $decoded=json_decode($body,true,flags:JSON_THROW_ON_ERROR);$decoded['verificationSubjectId']=rawurldecode($matches[1]);$body=json_encode($decoded,JSON_THROW_ON_ERROR)."\n";
        }
        return new HttpResponse($status,['Content-Type'=>'application/json'],$body);
    }
    public function requests():array{return $this->requests;}
}
