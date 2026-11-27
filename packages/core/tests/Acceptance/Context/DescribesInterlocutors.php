<?php

declare(strict_types=1);

namespace Tests\Core\Acceptance\Context;

use Prospecter\Core\PersonalData\Domain\Model\PersonId;
use Prospecter\Core\Prospecting\Application\Agenda\Interlocutors;
use Prospecter\Core\Prospecting\Application\Contact\ConsultOrganizationsOfContacts\OrganizationOfContact;
use Prospecter\Core\Prospecting\Application\ExchangeInTimeline;

/**
 * Describes who an agenda's exchange is with (ADR 0217): the person, by their name as the member reads it, or the
 * organization met alone, and the organizations the person works for.
 */
trait DescribesInterlocutors
{
    /**
     * @return array{'montré avec': string, 'ses organisations': string}
     */
    private function shownInterlocutorOf(ExchangeInTimeline $exchange, Interlocutors $interlocutors): array
    {
        return [
            'montré avec' => $this->nameOfTheInterlocutor($exchange, $interlocutors),
            'ses organisations' => implode(', ', array_map(static fn (OrganizationOfContact $organization): string => $organization->fullName, $interlocutors->organizationsOfContacts[$exchange->personId ?? ''] ?? [])),
        ];
    }

    private function nameOfTheInterlocutor(ExchangeInTimeline $exchange, Interlocutors $interlocutors): string
    {
        return null === $exchange->personId
            ? $interlocutors->organizationsMetAlone[$exchange->organizationIds[0]]->fullName ?? 'inconnue'
            : $this->scenario->nameOfPersonAsRead(new PersonId($exchange->personId));
    }
}
