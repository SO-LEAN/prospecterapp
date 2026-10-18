<?php

namespace App\Prospecting\Persistence;

use App\Shared\Persistence\Documents;
use App\Shared\Persistence\Hydrator;
use Prospecter\Core\Prospecting\Domain\Channel;
use Prospecter\Core\Prospecting\Domain\Direction;
use Prospecter\Core\Prospecting\Domain\Exchange;
use Prospecter\Core\Prospecting\Domain\ExchangeFacts;
use Prospecter\Core\Prospecting\Domain\ExchangeId;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

/**
 * An exchange as a plain document of its DynamoDB item, and back (ADR 0048): its facts as they are, its
 * recorded events never.
 */
final class ExchangeDocument
{
    /**
     * @return array<string, mixed>
     */
    public static function of(Exchange $exchange): array
    {
        $state = Hydrator::stateOf($exchange);
        $facts = $state['facts'];

        return [
            'id' => (string) $state['id'],
            'accountId' => (string) $state['accountId'],
            'createdBy' => (string) $state['createdBy'],
            'contact' => (string) $state['contact'],
            'keptFor' => array_map(static fn (OrganizationId $organization): string => (string) $organization, $state['keptFor']),
            'facts' => [
                'channel' => $facts->channel->value,
                'direction' => $facts->direction->value,
                'occurredAt' => Documents::moment($facts->occurredAt),
                'text' => $facts->text,
                'missed' => $facts->missed,
            ],
            'createdAt' => Documents::moment($state['createdAt']),
            'modifiedAt' => Documents::moment($state['modifiedAt']),
        ];
    }

    /**
     * @param array<string, mixed> $document
     */
    public static function exchange(array $document): Exchange
    {
        $facts = $document['facts'];

        return Hydrator::restore(Exchange::class, [
            'id' => new ExchangeId($document['id']),
            'accountId' => new AccountId($document['accountId']),
            'createdBy' => new MemberId($document['createdBy']),
            'contact' => new PersonId($document['contact']),
            'keptFor' => array_map(static fn (string $organization): OrganizationId => new OrganizationId($organization), $document['keptFor']),
            'facts' => new ExchangeFacts(Channel::from($facts['channel']), Direction::from($facts['direction']), Documents::momentOf($facts['occurredAt']), $facts['text'], $facts['missed']),
            'createdAt' => Documents::momentOf($document['createdAt']),
            'modifiedAt' => Documents::momentOf($document['modifiedAt']),
        ]);
    }
}
