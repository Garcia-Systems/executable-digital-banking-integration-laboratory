<?php
declare(strict_types=1);
namespace Harbor\DigitalBankingLab\Http;
use Harbor\DigitalBankingLab\Api\{ApiError,IntegrationFailureApiMapper,MemberVerificationPresenter};
use Harbor\DigitalBankingLab\Application\{GetMemberVerification,IntegrationFailure};
use Harbor\DigitalBankingLab\Domain\Member\MemberId;
final readonly class MemberVerificationController {
 public function __construct(private GetMemberVerification $service,private MemberVerificationPresenter $presenter,private IntegrationFailureApiMapper $failures=new IntegrationFailureApiMapper()){}
 public function show(string $identifier):Response {
  try{$id=new MemberId($identifier);return Response::json(200,$this->presenter->present($this->service->execute($id)));}
  catch(\InvalidArgumentException){return Response::json(404,(new ApiError('member_not_found','Member was not found.'))->toArray());}
  catch(IntegrationFailure $failure){$mapped=$this->failures->map($failure);return Response::json($mapped['status'],$mapped['error']->toArray());}
 }
}
