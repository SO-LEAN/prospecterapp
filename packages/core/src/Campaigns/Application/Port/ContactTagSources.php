<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Application\Port;

use Prospecter\Core\Campaigns\Domain\Model\ContactTags;
use Prospecter\Core\Campaigns\Domain\Model\Reach;
use Prospecter\Core\Campaigns\Domain\Model\Tag;

/**
 * What the tags of a contact are derived from, as the table keeps it (ADR 0141): its tagging, the taggings of the
 * organizations whose fact names it, and how its fact says it is reached.
 */
final readonly class ContactTagSources
{
    /**
     * @param list<string>                                                       $own            the names of its tags, without their mark
     * @param array<string, list<string>>                                        $byOrganization the names of the tags of each organization naming it, by its
     *                                                                                           identity
     * @param array{email: bool, phone: bool, mobile: bool, linkedIn: bool}|null $reach          null without a fact of it
     */
    public function __construct(
        public string $contactId,
        public array $own,
        public array $byOrganization,
        public ?array $reach,
    ) {
    }

    #[\NoDiscard]
    public function tags(): ContactTags
    {
        return ContactTags::of(self::tagsNamed($this->own), array_map(self::tagsNamed(...), $this->byOrganization), $this->reached());
    }

    private function reached(): Reach
    {
        return null === $this->reach
            ? Reach::none()
            : new Reach($this->reach['email'], $this->reach['phone'], $this->reach['mobile'], $this->reach['linkedIn']);
    }

    /**
     * @param list<string> $names
     *
     * @return list<Tag>
     */
    private static function tagsNamed(array $names): array
    {
        return array_map(static fn (string $name): Tag => Tag::written(Tag::MARK.$name), $names);
    }
}
