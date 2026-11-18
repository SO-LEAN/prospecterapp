<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\ConsultTimelineIntents;

use Prospecter\Core\Prospecting\Domain\Port\Prospectors;
use Prospecter\Core\Prospecting\Domain\Service\ContactOrganizations;
use Prospecter\Core\SharedKernel\Application\QueryHandler;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

/**
 * A person who is no contact of the account has no timeline: nothing is offered on it. A contact waiting for another
 * member to attach them is only read (ADR 0146).
 */
final readonly class ConsultTimelineIntentsHandler implements QueryHandler
{
    public function __construct(
        private Prospectors $prospectors,
        private ContactOrganizations $contactOrganizations,
    ) {
    }

    public function __invoke(ConsultTimelineIntents $query, ConsultTimelineIntentsOutput $output): void
    {
        $prospector = $this->prospectors->prospectorFrom(new MemberId($query->prospectorId));
        $contact = new PersonId($query->personId);

        $output->present(TimelineIntentsResponse::of($this->contactOrganizations->timelineIntentsOf($contact, $prospector)));
    }
}
