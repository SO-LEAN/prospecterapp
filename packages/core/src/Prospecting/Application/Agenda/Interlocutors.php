<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\Agenda;

use Prospecter\Core\Prospecting\Application\Contact\ConsultOrganizationsOfContacts\OrganizationOfContact;
use Prospecter\Core\Prospecting\Application\ExchangeInTimeline;
use Prospecter\Core\Prospecting\Application\OrganizationSheet;

/**
 * Who an agenda's exchanges are with, as Prospecting knows them (ADR 0217): the organizations met with no contact, and
 * the organizations each contact works for. The persons are PersonalData's, named next to the agenda by their ids.
 */
final readonly class Interlocutors
{
    /**
     * @param array<string, OrganizationSheet>           $organizationsMetAlone   the organizations still known that exchanges are held with directly, by id
     * @param array<string, list<OrganizationOfContact>> $organizationsOfContacts by person id
     */
    public function __construct(
        public array $organizationsMetAlone = [],
        public array $organizationsOfContacts = [],
    ) {
    }

    /**
     * Keeps the exchanges whose interlocutor is still known. A person always is, by their id. Stopping prospecting
     * erases an organization at once and its exchanges later, so an organization no longer known leaves out its
     * exchanges.
     *
     * @param list<ExchangeInTimeline> $exchanges
     *
     * @return list<ExchangeInTimeline>
     */
    public function known(array $exchanges): array
    {
        return array_values(array_filter($exchanges, fn (ExchangeInTimeline $exchange): bool => null !== $exchange->personId || isset($this->organizationsMetAlone[$exchange->organizationIds[0]])));
    }
}
