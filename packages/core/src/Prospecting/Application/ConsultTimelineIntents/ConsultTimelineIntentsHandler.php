<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\ConsultTimelineIntents;

use Prospecter\Core\Prospecting\Domain\Model\ContactId;
use Prospecter\Core\Prospecting\Domain\Port\Prospectors;
use Prospecter\Core\Prospecting\Domain\Service\ContactOrganizations;
use Prospecter\Core\SharedKernel\Application\QueryHandler;
use Prospecter\Core\SharedKernel\Domain\MemberId;

/**
 * A person who is not a contact of the account has no timeline, so the timeline offers nothing. A contact who waits
 * for another member to attach them can only be read (ADR 0146).
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
        $contact = new ContactId($query->personId);

        $output->present(TimelineIntentsResponse::of($this->contactOrganizations->timelineIntentsOf($contact, $prospector)));
    }
}
