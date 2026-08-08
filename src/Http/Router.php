<?php

declare(strict_types=1);

namespace Harbor\DigitalBankingLab\Http;

use Harbor\DigitalBankingLab\Api\ApiError;

final readonly class Router
{
    public function __construct(private MemberController $members, private MemberFinancialOverviewController $overviews, private MemberActivityProfileController $activityProfiles, private TransferPreviewController $transferPreviews,private MemberVerificationController $verifications)
    {
    }

    public function dispatch(string $method, string $path, string $body = ''): Response
    {
        $path = rawurldecode(parse_url($path, PHP_URL_PATH) ?: '/');
        if (preg_match('#^/api/members/([^/]+)/verification$#',$path,$matches)===1) {
            if($method!=='GET')return Response::json(405,(new ApiError('method_not_allowed','Method is not allowed for this resource.'))->toArray());
            return $this->verifications->show($matches[1]);
        }
        if (preg_match('#^/api/members/([^/]+)/transfer-preview$#', $path, $matches) === 1) {
            if ($method === 'OPTIONS') return new Response(204, [], '');
            if ($method !== 'POST') return Response::json(405, (new ApiError('method_not_allowed', 'Method is not allowed for this resource.'))->toArray());
            return $this->transferPreviews->create($matches[1], $body);
        }
        if (preg_match('#^/api/members/([^/]+)/activity-profile$#', $path, $matches) === 1) {
            if ($method !== 'GET') return Response::json(405, (new ApiError('method_not_allowed', 'Method is not allowed for this resource.'))->toArray());
            return $this->activityProfiles->show($matches[1]);
        }
        if (preg_match('#^/api/members/([^/]+)/financial-overview$#', $path, $matches) === 1) {
            if ($method !== 'GET') return Response::json(405, (new ApiError('method_not_allowed', 'Method is not allowed for this resource.'))->toArray());
            return $this->overviews->show($matches[1]);
        }
        if (preg_match('#^/api/members/(.*)$#', $path, $matches) === 1 && !str_contains($matches[1], '/')) {
            if ($method !== 'GET') {
                return Response::json(405, (new ApiError('method_not_allowed', 'Method is not allowed for this resource.'))->toArray());
            }
            return $this->members->show($matches[1]);
        }
        return Response::json(404, (new ApiError('route_not_found', 'API route was not found.'))->toArray());
    }
}
