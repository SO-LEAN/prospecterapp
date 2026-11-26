<?php

declare(strict_types=1);

namespace App\Identification;

use App\Identification\Resource\CandidateResource;
use App\Identification\Resource\IdentificationResource;
use App\Identification\Resource\IdentificationsResource;
use Prospecter\Core\PersonalData\Application\Identification\Candidate;
use Prospecter\Core\PersonalData\Application\Identification\Identification;
use Prospecter\Core\PersonalData\Application\Identification\IdentifyPersonsAndOrganizations\IdentifyPersonsAndOrganizationsOutput;

/**
 * Presents what the clues designate, persons and organizations, each in the order given (ADR 0085, 0173).
 */
final class IdentificationsPresenter implements IdentifyPersonsAndOrganizationsOutput
{
    public private(set) IdentificationsResource $resource;

    public function present(array $persons, array $organizations): void
    {
        $this->resource = new IdentificationsResource(array_map(self::resourceOf(...), $persons), array_map(self::resourceOf(...), $organizations));
    }

    private static function resourceOf(Identification $identification): IdentificationResource
    {
        return new IdentificationResource($identification->status, array_map(
            static fn (Candidate $candidate): CandidateResource => new CandidateResource($candidate->id, $candidate->name),
            $identification->candidates,
        ));
    }
}
