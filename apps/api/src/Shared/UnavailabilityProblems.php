<?php

declare(strict_types=1);

namespace App\Shared;

use App\Shared\Resource\ProblemResource;
use Symfony\Component\HttpFoundation\Response;

/**
 * Maps an unreachable service, on AWS or outside, to a 503 problem. The problem carries a reason, and the client
 * retries later.
 */
final readonly class UnavailabilityProblems
{
    public function of(ServiceUnavailable $failure): ProblemResource
    {
        return new ProblemResource('Service Unavailable', Response::HTTP_SERVICE_UNAVAILABLE, $failure->getMessage(), reason: $failure->reason);
    }
}
