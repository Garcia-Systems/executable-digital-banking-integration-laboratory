<?php
declare(strict_types=1);
namespace Harbor\DigitalBankingLab\Integration\ClearVerify;
use Harbor\DigitalBankingLab\Application\{IntegrationFailure,IntegrationFailureCategory,IntegrationOperation,RetryDisposition};
use Harbor\DigitalBankingLab\Infrastructure\Http\{HttpClient,HttpTimeoutException,HttpTransportException};
use Harbor\DigitalBankingLab\Integration\ClearVerify\Model\{ClearVerifyResponse,ClearVerifySubjectId};
final readonly class ClearVerifyRestClient {
 public function __construct(private HttpClient $http,private string $baseUrl='https://clearverify.invalid'){}
 public function status(ClearVerifySubjectId $id):ClearVerifyResponse {
  try{$response=$this->http->request('GET',rtrim($this->baseUrl,'/').'/v1/verification-subjects/'.rawurlencode($id->value).'/status',['Accept'=>'application/json']);}
  catch(HttpTimeoutException $e){throw $this->failure(IntegrationFailureCategory::TIMEOUT,'CLEARVERIFY_TIMEOUT',RetryDisposition::RETRYABLE,$e);}
  catch(HttpTransportException $e){throw $this->failure(IntegrationFailureCategory::TEMPORARY_UNAVAILABLE,'CLEARVERIFY_UNAVAILABLE',RetryDisposition::RETRYABLE,$e);}
  if($response->statusCode!==200)throw $this->failure(IntegrationFailureCategory::EXTERNAL_ERROR,'CLEARVERIFY_HTTP_'.$response->statusCode,RetryDisposition::UNKNOWN);
  try{$body=json_decode($response->body,true,512,JSON_THROW_ON_ERROR);}catch(\JsonException $e){throw $this->failure(IntegrationFailureCategory::INVALID_EXTERNAL_RESPONSE,'CLEARVERIFY_INVALID_JSON',RetryDisposition::NOT_RETRYABLE,$e);}
  if(!is_array($body)||!is_string($body['verificationSubjectId']??null)||!is_string($body['status']??null)||!is_string($body['reference']??null))throw $this->failure(IntegrationFailureCategory::INVALID_EXTERNAL_RESPONSE,'CLEARVERIFY_INCOMPLETE_RESPONSE',RetryDisposition::NOT_RETRYABLE);
  if($body['verificationSubjectId']!==$id->value)throw $this->failure(IntegrationFailureCategory::INVALID_EXTERNAL_RESPONSE,'CLEARVERIFY_SUBJECT_MISMATCH',RetryDisposition::NOT_RETRYABLE);
  return new ClearVerifyResponse(new ClearVerifySubjectId($body['verificationSubjectId']),$body['status'],$body['reference']);
 }
 private function failure(IntegrationFailureCategory $category,string $code,RetryDisposition $retry,?\Throwable $previous=null):IntegrationFailure{return new IntegrationFailure($category,$retry,'Member verification service failed.','ClearVerify Identity Services',IntegrationOperation::MEMBER_VERIFICATION,$code,$previous);}
}
