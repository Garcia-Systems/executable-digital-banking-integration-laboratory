<?php

declare(strict_types=1);

namespace Harbor\DigitalBankingLab\Http;

use Harbor\DigitalBankingLab\Api\ApiError;

final readonly class Router
{
    public function __construct(private MemberController $members, private MemberFinancialOverviewController $overviews)
    {
    }

    public function dispatch(string $method, string $path): Response
    {
        $path = rawurldecode(parse_url($path, PHP_URL_PATH) ?: '/');
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
