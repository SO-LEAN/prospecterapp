<?php

declare(strict_types=1);

namespace App\Shared;

use App\Shared\Resource\ProblemResource;
use Symfony\Component\HttpFoundation\Response;

/**
 * The problem a service out of reach, of AWS or outside, is: a 503 named by its reason, to try again later.
 */
final readonly class UnavailabilityProblems
{
    public function of(ServiceUnavailable $failure): ProblemResource
    {
        return new ProblemResource('Service Unavailable', Response::HTTP_SERVICE_UNAVAILABLE, $failure->getMessage(), reason: $failure->reason);
    }
}
