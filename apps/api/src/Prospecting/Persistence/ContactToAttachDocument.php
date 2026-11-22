<?php

declare(strict_types=1);

namespace App\Prospecting\Persistence;

use App\Shared\Persistence\Documents;
use App\Shared\Persistence\Hydrator;
use Prospecter\Core\Prospecting\Application\ContactToAttachInList;
use Prospecter\Core\Prospecting\Domain\Model\ContactToAttach;
use Prospecter\Core\Prospecting\Domain\Policy\UnattachedErasure;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

/**
 * A contact to attach as a plain document of its DynamoDB item, and back (ADR 0048), its recorded events never; the
 * clock of its last meeting by the name of its zone (ADR 0146).
 */
final class ContactToAttachDocument
{
    /**
     * @return array<string, mixed>
     */
    public static function of(ContactToAttach $contactToAttach): array
    {
        $state = Hydrator::stateOf($contactToAttach);

        return [
            'id' => (string) $state['id'],
            'accountId' => (string) $state['accountId'],
            'holder' => (string) $state['holder'],
            'lastMeetingAt' => Documents::moment($state['lastMeetingAt']),
            'clock' => $state['clock']->getName(),
            'createdAt' => Documents::moment($state['createdAt']),
            'modifiedAt' => Documents::moment($state['modifiedAt']),
        ];
    }

    /**
     * @param array<string, mixed> $document
     */
    public static function contactToAttach(array $document): ContactToAttach
    {
        return Hydrator::restore(ContactToAttach::class, [
            'id' => new PersonId($document['id']),
            'accountId' => new AccountId($document['accountId']),
            'holder' => new MemberId($document['holder']),
            'lastMeetingAt' => Documents::momentOf($document['lastMeetingAt']),
            'clock' => new \DateTimeZone($document['clock']),
            'createdAt' => Documents::momentOf($document['createdAt']),
            'modifiedAt' => Documents::momentOf($document['modifiedAt']),
        ]);
    }

    /**
     * The read model, without restoring the contact to attach (ADR 0088).
     *
     * @param array<string, mixed> $document
     */
    public static function inList(array $document): ContactToAttachInList
    {
        $lastMeetingAt = Documents::momentOf($document['lastMeetingAt']);

        return new ContactToAttachInList($document['id'], $lastMeetingAt, self::erasableFrom($document));
    }

    /**
     * @param array<string, mixed> $document
     */
    public static function erasableFrom(array $document): \DateTimeImmutable
    {
        return UnattachedErasure::from(Documents::momentOf($document['lastMeetingAt']), new \DateTimeZone($document['clock']))->setTimezone(new \DateTimeZone('UTC'));
    }
}
