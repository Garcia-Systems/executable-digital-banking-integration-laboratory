<?php
declare(strict_types=1);
namespace Harbor\DigitalBankingLab\Http;

use Harbor\DigitalBankingLab\Api\{ApiError, IntegrationFailureApiMapper, MemberFinancialOverviewPresenter};
use Harbor\DigitalBankingLab\Application\{GetMemberFinancialOverview, IntegrationFailure, MemberNotFound};
use Harbor\DigitalBankingLab\Domain\Member\MemberId;

final readonly class MemberFinancialOverviewController
{
    public function __construct(private GetMemberFinancialOverview $useCase, private MemberFinancialOverviewPresenter $presenter, private IntegrationFailureApiMapper $failures = new IntegrationFailureApiMapper()) {}
    public function show(string $identifier): Response
    {
        try { $id = new MemberId($identifier); }
        catch (\InvalidArgumentException) { return Response::json(400, (new ApiError('invalid_member_id', 'Member identifier is invalid.'))->toArray()); }
        try { return Response::json(200, $this->presenter->present($this->useCase->execute($id))); }
        catch (MemberNotFound) { return Response::json(404, (new ApiError('member_not_found', 'Member was not found.'))->toArray()); }
        catch (IntegrationFailure $failure) {
            $mapping = $this->failures->map($failure);
            return Response::json($mapping['status'], $mapping['error']->toArray());
        } catch (\Throwable) {
            return Response::json(500, (new ApiError('service_unavailable', 'Member information is temporarily unavailable.'))->toArray());
        }
    }
}
