<?php

declare(strict_types=1);

namespace App\PersonalData\AntiCorruption;

use Prospecter\Core\PersonalData\Application\Identification\Candidate;
use Prospecter\Core\PersonalData\Application\Identification\Identification;
use Prospecter\Core\PersonalData\Application\Identification\OrganizationClues;
use Prospecter\Core\PersonalData\Application\Port\OrganizationIdentificationRetriever;
use Prospecter\Core\Prospecting\Contract\Service\IdentifyOrganizations\CandidateResponse;
use Prospecter\Core\Prospecting\Contract\Service\IdentifyOrganizations\IdentificationResponse;
use Prospecter\Core\Prospecting\Contract\Service\IdentifyOrganizations\IdentifyOrganizations;
use Prospecter\Core\Prospecting\Contract\Service\IdentifyOrganizations\IdentifyOrganizationsService;
use Prospecter\Core\Prospecting\Contract\Service\IdentifyOrganizations\KeptOrganizationIdentifications;
use Prospecter\Core\Prospecting\Contract\Service\IdentifyOrganizations\OrganizationClues as ProspectingOrganizationClues;
use Prospecter\Core\SharedKernel\Domain\Identity\MemberId;

/**
 * Anticorruption layer (ADR 0045, 0089, 0173, 0217). Prospecting identifies the organizations, all in one call.
 */
final readonly class ProspectingOrganizationIdentifications implements OrganizationIdentificationRetriever
{
    public function __construct(private IdentifyOrganizationsService $identifyOrganizationsService)
    {
    }

    /**
     * @param list<OrganizationClues> $organizations
     */
    public function identify(MemberId $reader, array $organizations): array
    {
        $kept = new KeptOrganizationIdentifications();
        ($this->identifyOrganizationsService)(new IdentifyOrganizations($reader->value, array_map(self::cluesOf(...), $organizations)), $kept);

        return array_map(self::identificationOf(...), $kept->identifications);
    }

    private static function cluesOf(OrganizationClues $clues): ProspectingOrganizationClues
    {
        return new ProspectingOrganizationClues($clues->name, $clues->siret, $clues->links, $clues->website, $clues->emails, $clues->city);
    }

    private static function identificationOf(IdentificationResponse $identification): Identification
    {
        $candidates = array_map(static fn (CandidateResponse $candidate): Candidate => new Candidate($candidate->id, $candidate->name), $identification->candidates);

        return match ($identification->status) {
            IdentificationResponse::KNOWN => Identification::known($candidates[0]),
            IdentificationResponse::NEW => Identification::new(),
            default => Identification::toConfirm($candidates),
        };
    }
}
