<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\ConsultTimelineIntents;

use Prospecter\Core\Prospecting\Domain\ContactOrganizations;
use Prospecter\Core\Prospecting\Domain\ProspectorService;
use Prospecter\Core\SharedKernel\Application\QueryHandler;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

/**
 * A person who is no contact of the account has no timeline: nothing is offered on it.
 */
final readonly class ConsultTimelineIntentsHandler implements QueryHandler
{
    public function __construct(
        private ProspectorService $prospectorService,
        private ContactOrganizations $contactOrganizations,
    ) {
    }

    public function __invoke(ConsultTimelineIntents $query): TimelineIntentsResponse
    {
        $prospector = $this->prospectorService->prospectorFrom(new MemberId($query->prospectorId));
        $isAContact = $this->contactOrganizations->isAContactOfTheAccount(new PersonId($query->personId), $prospector);

        return TimelineIntentsResponse::of($isAContact ? $prospector->timelineIntents() : []);
    }
}
