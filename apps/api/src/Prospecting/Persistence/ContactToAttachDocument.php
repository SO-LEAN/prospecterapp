<?php

declare(strict_types=1);

namespace App\Prospecting\Persistence;

use App\Shared\Persistence\Documents;
use App\Shared\Persistence\Hydrator;
use App\Shared\Typed;
use Prospecter\Core\Prospecting\Application\ContactToAttachInList;
use Prospecter\Core\Prospecting\Domain\Model\ContactId;
use Prospecter\Core\Prospecting\Domain\Model\ContactToAttach;
use Prospecter\Core\Prospecting\Domain\Rule\UnattachedErasure;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\MemberId;

/**
 * Turns a contact to attach into a plain document for its DynamoDB item, and back (ADR 0048). Its recorded events are
 * never stored. The clock of its last meeting is stored as its time zone's name (ADR 0146).
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
            'id' => Typed::object($state['id'], ContactId::class)->value,
            'accountId' => Typed::object($state['accountId'], AccountId::class)->value,
            'holder' => Typed::object($state['holder'], MemberId::class)->value,
            'lastMeetingAt' => Documents::moment(Typed::object($state['lastMeetingAt'], \DateTimeImmutable::class)),
            'clock' => Typed::object($state['clock'], \DateTimeZone::class)->getName(),
            'createdAt' => Documents::moment(Typed::object($state['createdAt'], \DateTimeImmutable::class)),
            'modifiedAt' => Documents::moment(Typed::object($state['modifiedAt'], \DateTimeImmutable::class)),
        ];
    }

    /**
     * Restores the contact to attach from its item's untyped document.
     */
    public static function contactToAttach(mixed $document): ContactToAttach
    {
        $document = Typed::document($document);

        return Hydrator::restore(ContactToAttach::class, [
            'id' => new ContactId(Typed::string($document['id'])),
            'accountId' => new AccountId(Typed::string($document['accountId'])),
            'holder' => new MemberId(Typed::string($document['holder'])),
            'lastMeetingAt' => Documents::momentOf(Typed::string($document['lastMeetingAt'])),
            'clock' => self::clockOf($document),
            'createdAt' => Documents::momentOf(Typed::string($document['createdAt'])),
            'modifiedAt' => Documents::momentOf(Typed::string($document['modifiedAt'])),
        ]);
    }

    /**
     * Builds the read model from its item's untyped document, without restoring the contact to attach (ADR 0088).
     */
    public static function inList(mixed $document): ContactToAttachInList
    {
        $document = Typed::document($document);

        return new ContactToAttachInList(Typed::string($document['id']), self::lastMeetingAtOf($document), self::erasableFrom($document));
    }

    /**
     * @param array<string, mixed> $document
     */
    public static function erasableFrom(array $document): \DateTimeImmutable
    {
        return UnattachedErasure::from(self::lastMeetingAtOf($document), self::clockOf($document))->setTimezone(new \DateTimeZone('UTC'));
    }

    /**
     * @param array<string, mixed> $document
     */
    private static function lastMeetingAtOf(array $document): \DateTimeImmutable
    {
        return Documents::momentOf(Typed::string($document['lastMeetingAt']));
    }

    /**
     * @param array<string, mixed> $document
     */
    private static function clockOf(array $document): \DateTimeZone
    {
        return new \DateTimeZone(Typed::string($document['clock']));
    }
}
