<?php

declare(strict_types=1);

namespace Tests\Core\Acceptance\Context;

use Prospecter\Core\Prospecting\Application\Agenda\Interlocutors;
use Prospecter\Core\Prospecting\Application\Contact\ConsultOrganizationsOfContacts\OrganizationOfContact;
use Prospecter\Core\Prospecting\Application\ExchangeInTimeline;

/**
 * Describes who an agenda's exchange is with, as the agenda's query names them (ADR 0217): the person's name as the
 * member reads it, or the organization met alone, and the organizations the person works for.
 */
trait DescribesInterlocutors
{
    /**
     * @return array{'montré avec': string, 'ses organisations': string}
     */
    private static function shownInterlocutorOf(ExchangeInTimeline $exchange, Interlocutors $interlocutors): array
    {
        return [
            'montré avec' => self::nameOfTheInterlocutor($exchange, $interlocutors),
            'ses organisations' => implode(', ', array_map(static fn (OrganizationOfContact $organization): string => $organization->fullName, $interlocutors->organizationsOfContacts[$exchange->personId ?? ''] ?? [])),
        ];
    }

    private static function nameOfTheInterlocutor(ExchangeInTimeline $exchange, Interlocutors $interlocutors): string
    {
        if (null === $exchange->personId) {
            return $interlocutors->organizationsMetAlone[$exchange->organizationIds[0]]->fullName ?? 'inconnue';
        }

        $person = $interlocutors->persons->contacts[$exchange->personId] ?? null;

        return null === $person ? 'inconnu' : trim(sprintf('%s %s', $person->firstName, $person->lastName));
    }
}
