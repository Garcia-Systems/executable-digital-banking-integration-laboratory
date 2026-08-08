<?php
declare(strict_types=1);
namespace Harbor\DigitalBankingLab\Http;
use Harbor\DigitalBankingLab\Api\{ApiError,MemberActivityProfilePresenter};
use Harbor\DigitalBankingLab\Application\{GetMemberActivityProfile,MemberNotFound};
use Harbor\DigitalBankingLab\Domain\{Clock};
use Harbor\DigitalBankingLab\Domain\Member\MemberId;
final readonly class MemberActivityProfileController
{
    public function __construct(private GetMemberActivityProfile $service,private MemberActivityProfilePresenter $presenter,private Clock $clock){}
    public function show(string $identifier): Response
    {
        try{$id=new MemberId($identifier);}catch(\InvalidArgumentException){return Response::json(400,(new ApiError('invalid_member_id','Member identifier is invalid.'))->toArray());}
        try{return Response::json(200,$this->presenter->present($this->service->execute($id,$this->clock->now())));}
        catch(MemberNotFound){return Response::json(404,(new ApiError('member_not_found','Member was not found.'))->toArray());}
        catch(\Throwable){return Response::json(500,(new ApiError('service_unavailable','Member information is temporarily unavailable.'))->toArray());}
    }
}
