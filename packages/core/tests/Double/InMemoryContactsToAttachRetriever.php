<?php

declare(strict_types=1);

namespace Tests\Core\Double;

use Prospecter\Core\Prospecting\Application\ContactToAttachInList;
use Prospecter\Core\Prospecting\Application\Port\ContactsToAttachRetriever;
use Prospecter\Core\Prospecting\Domain\Model\ContactId;
use Prospecter\Core\Prospecting\Domain\Model\ContactToAttach;
use Prospecter\Core\Prospecting\Domain\Policy\UnattachedErasure;
use Prospecter\Core\SharedKernel\Domain\MemberId;

/**
 * The contacts to attach that the repository saved, read without their getters. The DynamoDB retriever reads
 * their items in the same way.
 */
final readonly class InMemoryContactsToAttachRetriever implements ContactsToAttachRetriever
{
    public function __construct(private InMemoryDatabase $database)
    {
    }

    public function heldBy(MemberId $holder): array
    {
        $held = array_map(self::inList(...), array_values(array_filter($this->database->contactsToAttach(), static fn (ContactToAttach $contactToAttach): bool => $contactToAttach->isHeldBy($holder))));
        usort($held, static fn (ContactToAttachInList $one, ContactToAttachInList $other): int => [$one->erasableFrom, $one->personId] <=> [$other->erasableFrom, $other->personId]);

        return $held;
    }

    public function findHeldBy(MemberId $holder, ContactId $person): ?ContactToAttachInList
    {
        $contactToAttach = $this->database->contactToAttach((string) $person);

        return true === $contactToAttach?->isHeldBy($holder) ? self::inList($contactToAttach) : null;
    }

    public function leftUnattachedAt(\DateTimeImmutable $at): array
    {
        $left = array_filter($this->database->contactsToAttach(), static fn (ContactToAttach $contactToAttach): bool => self::inList($contactToAttach)->erasableFrom <= $at);

        return array_values(array_map(static fn (ContactToAttach $contactToAttach): string => (string) $contactToAttach->id(), $left));
    }

    private static function inList(ContactToAttach $contactToAttach): ContactToAttachInList
    {
        $state = StateOf::the($contactToAttach);

        return new ContactToAttachInList((string) $contactToAttach->id(), $state->object('lastMeetingAt', \DateTimeImmutable::class), UnattachedErasure::from($state->object('lastMeetingAt', \DateTimeImmutable::class), $state->object('clock', \DateTimeZone::class)));
    }
}
