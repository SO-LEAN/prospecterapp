<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Domain\Model;

use Prospecter\Core\Campaigns\Domain\Event\ContactTagsChanged;
use Prospecter\Core\Campaigns\Domain\Event\OrganizationTagsChanged;
use Prospecter\Core\Campaigns\Domain\Exception\AutomaticTag;
use Prospecter\Core\Campaigns\Domain\Exception\TagNotCarried;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\Event\DomainEvent;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;
use Prospecter\Core\SharedKernel\Domain\PersonId;
use Prospecter\Core\SharedKernel\Domain\RecordsEvents;
use Prospecter\Core\SharedKernel\Domain\ReleasesEvents;

/**
 * Aggregate root: the tagging of an organization or of a contact of an account, the tags its members put on it by
 * hand, source data of Campaigns (ADR 0138). The automatic tags are never among them.
 */
final class Tagging implements ReleasesEvents
{
    use RecordsEvents;

    /** @var list<Tag> in the order of their names */
    private array $tags = [];
    private \DateTimeImmutable $modifiedAt;

    private function __construct(
        private readonly AccountId $accountId,
        private readonly Holder $holder,
        private readonly \DateTimeImmutable $createdAt,
    ) {
        $this->modifiedAt = $createdAt;
    }

    public static function of(AccountId $accountId, Holder $holder, \DateTimeImmutable $at): self
    {
        return new self($accountId, $holder, $at);
    }

    /**
     * A tag already carried is carried once.
     *
     * @param list<Tag> $tags
     *
     * @throws AutomaticTag
     */
    public function tagWith(array $tags, MemberId $by, \DateTimeImmutable $at): void
    {
        array_map(self::ensurePutByHand(...), $tags);

        foreach ($tags as $tag) {
            $this->carry($tag);
        }

        usort($this->tags, static fn (Tag $one, Tag $other): int => strcmp($one->name, $other->name));
        $this->changedBy($by, $at);
    }

    /**
     * @throws TagNotCarried
     */
    public function takeOff(Tag $tag, MemberId $by, \DateTimeImmutable $at): void
    {
        if (!$this->carries($tag)) {
            throw TagNotCarried::named($tag);
        }

        $this->tags = array_values(array_filter($this->tags, static fn (Tag $carried): bool => !$carried->equals($tag)));
        $this->changedBy($by, $at);
    }

    public function accountId(): AccountId
    {
        return $this->accountId;
    }

    public function holder(): Holder
    {
        return $this->holder;
    }

    private static function ensurePutByHand(Tag $tag): void
    {
        if ($tag->isAutomatic()) {
            throw AutomaticTag::named($tag);
        }
    }

    private function carry(Tag $tag): void
    {
        if (!$this->carries($tag)) {
            $this->tags[] = $tag;
        }
    }

    private function carries(Tag $tag): bool
    {
        return array_any($this->tags, $tag->equals(...));
    }

    private function changedBy(MemberId $by, \DateTimeImmutable $at): void
    {
        $this->modifiedAt = $at;
        $this->recordThat($this->tagsChanged($by, $at));
    }

    private function tagsChanged(MemberId $by, \DateTimeImmutable $at): DomainEvent
    {
        return match ($this->holder->kind) {
            HolderKind::Organization => new OrganizationTagsChanged($this->accountId, $by, new OrganizationId($this->holder->id->value), $at),
            HolderKind::Contact => new ContactTagsChanged($this->accountId, $by, new PersonId($this->holder->id->value), $at),
        };
    }
}
