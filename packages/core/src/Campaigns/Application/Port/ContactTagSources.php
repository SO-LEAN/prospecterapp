<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Application\Port;

use Prospecter\Core\Campaigns\Domain\Model\ContactTags;
use Prospecter\Core\Campaigns\Domain\Model\Locality;
use Prospecter\Core\Campaigns\Domain\Model\Reach;
use Prospecter\Core\Campaigns\Domain\Model\Tag;

/**
 * What a contact's tags are derived from, as the table keeps it (ADR 0141). It holds the contact's tagging, and the
 * taggings of the organizations whose fact names it. It holds where those organizations' facts say they are (ADR 0142),
 * and how the contact's fact says it is reached.
 */
final readonly class ContactTagSources
{
    /**
     * @param list<string>                                                       $own            its tags' names, without their `#`
     * @param array<string, list<string>>                                        $byOrganization the tag names of each organization that names it,
     *                                                                                           by organization id
     * @param array{email: bool, phone: bool, mobile: bool, linkedIn: bool}|null $reach          null without a fact about it
     * @param array<string, array{municipalityCode: ?string, country: ?string}>  $localities     where each organization that
     *                                                                                           names it is, by organization id
     */
    public function __construct(
        public string $contactId,
        public array $own,
        public array $byOrganization,
        public ?array $reach,
        public array $localities,
    ) {
    }

    #[\NoDiscard]
    public function tags(): ContactTags
    {
        return ContactTags::of(self::tagsNamed($this->own), $this->inheritable(), $this->reached());
    }

    /**
     * @return array<string, list<Tag>> the tags put by hand on each organization that names it, and the tags for where it is
     */
    private function inheritable(): array
    {
        $byOrganization = array_map(self::tagsNamed(...), $this->byOrganization);

        foreach ($this->localities as $organization => $locality) {
            $byOrganization[$organization] = [...$byOrganization[$organization] ?? [], ...Locality::read($locality)->geographicTags()];
        }

        return $byOrganization;
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
