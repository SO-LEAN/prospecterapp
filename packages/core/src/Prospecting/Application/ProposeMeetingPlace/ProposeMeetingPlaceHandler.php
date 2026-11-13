<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\ProposeMeetingPlace;

use Prospecter\Core\Prospecting\Application\OrganizationSheet;
use Prospecter\Core\Prospecting\Application\Port\OrganizationSheetRetriever;
use Prospecter\Core\Prospecting\Domain\Port\Prospectors;
use Prospecter\Core\Prospecting\Domain\Service\ContactOrganizations;
use Prospecter\Core\SharedKernel\Application\QueryHandler;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

/**
 * The first organization of the contact with an address gives the place; none with one, nothing is proposed.
 */
final readonly class ProposeMeetingPlaceHandler implements QueryHandler
{
    public function __construct(
        private OrganizationSheetRetriever $organizationSheetRetriever,
        private Prospectors $prospectors,
        private ContactOrganizations $contactOrganizations,
    ) {
    }

    public function __invoke(ProposeMeetingPlace $query, ProposeMeetingPlaceOutput $output): void
    {
        $prospector = $this->prospectors->prospectorFrom(new MemberId($query->prospectorId));
        $organizations = $this->contactOrganizations->of(new PersonId($query->personId), $prospector->accountId);
        $located = array_find(array_map($this->organizationSheetRetriever->get(...), $organizations), self::hasAnAddress(...));

        $output->present(null === $located ? null : new MeetingPlace($located->id, $located->street, $located->postalCode, $located->city, $located->country));
    }

    private static function hasAnAddress(OrganizationSheet $sheet): bool
    {
        return null !== $sheet->street || null !== $sheet->postalCode || null !== $sheet->city;
    }
}
