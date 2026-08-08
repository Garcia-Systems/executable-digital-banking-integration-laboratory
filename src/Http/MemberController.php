<?php

declare(strict_types=1);

namespace Harbor\DigitalBankingLab\Http;

use Harbor\DigitalBankingLab\Api\{ApiError, IntegrationFailureApiMapper, MemberSummaryPresenter};
use Harbor\DigitalBankingLab\Application\{GetMemberSummary, IntegrationFailure, MemberNotFound};
use Harbor\DigitalBankingLab\Domain\Member\MemberId;

final readonly class MemberController
{
    public function __construct(private GetMemberSummary $getMemberSummary, private MemberSummaryPresenter $presenter, private IntegrationFailureApiMapper $failureMapper = new IntegrationFailureApiMapper())
    {
    }

    public function show(string $identifier): Response
    {
        try {
            $memberId = new MemberId($identifier);
        } catch (\InvalidArgumentException) {
            return Response::json(400, (new ApiError('invalid_member_id', 'Member identifier is invalid.'))->toArray());
        }

        try {
            return Response::json(200, $this->presenter->present($this->getMemberSummary->execute($memberId)));
        } catch (MemberNotFound) {
            return Response::json(404, (new ApiError('member_not_found', 'Member was not found.'))->toArray());
        } catch (IntegrationFailure $failure) {
            $mapping = $this->failureMapper->map($failure);
            return Response::json($mapping['status'], $mapping['error']->toArray());
        } catch (\Throwable) {
            return Response::json(500, (new ApiError('service_unavailable', 'Member information is temporarily unavailable.'))->toArray());
        }
    }
}
