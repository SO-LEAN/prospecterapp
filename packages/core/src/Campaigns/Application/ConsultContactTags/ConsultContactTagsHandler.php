<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Application\ConsultContactTags;

use Prospecter\Core\Campaigns\Application\Port\ContactTagSources;
use Prospecter\Core\Campaigns\Application\Port\ContactTagsRetriever;
use Prospecter\Core\Campaigns\Domain\Model\ContactTags;
use Prospecter\Core\Campaigns\Domain\Model\InheritedTag;
use Prospecter\Core\Campaigns\Domain\Model\Reach;
use Prospecter\Core\Campaigns\Domain\Model\Tag;
use Prospecter\Core\Campaigns\Domain\Port\Taggers;
use Prospecter\Core\Campaigns\Domain\Service\TaggableHolders;
use Prospecter\Core\SharedKernel\Application\QueryHandler;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

/**
 * Every tag contacts of the account of the member carry, their own, inherited and automatic (ADR 0141); a person who
 * is no contact of the account is unknown to it.
 */
final readonly class ConsultContactTagsHandler implements QueryHandler
{
    public function __construct(
        private ContactTagsRetriever $contactTagsRetriever,
        private Taggers $taggers,
        private TaggableHolders $holders,
    ) {
    }

    public function __invoke(ConsultContactTags $query, ConsultContactTagsOutput $output): void
    {
        $accountId = $this->taggers->accountOf(new MemberId($query->memberId));
        $contacts = array_map(static fn (string $contact): PersonId => new PersonId($contact), $query->contactIds);
        $this->holders->ensureContactsOf($accountId, $contacts);
        $output->present(array_map(self::responseOf(...), $this->contactTagsRetriever->sourcesOf($accountId, $contacts)));
    }

    private static function responseOf(ContactTagSources $sources): ContactTagsResponse
    {
        $tags = ContactTags::of(self::tagsNamed($sources->own), array_map(self::tagsNamed(...), $sources->byOrganization), self::reachOf($sources));

        return new ContactTagsResponse(
            $sources->contactId,
            self::written($tags->own),
            array_map(static fn (InheritedTag $inherited): InheritedTagResponse => new InheritedTagResponse((string) $inherited->tag, array_map(static fn (OrganizationId $organization): string => $organization->value, $inherited->from)), $tags->inherited),
            self::written($tags->automatic),
        );
    }

    private static function reachOf(ContactTagSources $sources): Reach
    {
        return null === $sources->reach
            ? Reach::none()
            : new Reach($sources->reach['email'], $sources->reach['phone'], $sources->reach['mobile'], $sources->reach['linkedIn']);
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

    /**
     * @param list<Tag> $tags
     *
     * @return list<string>
     */
    private static function written(array $tags): array
    {
        return array_map(static fn (Tag $tag): string => (string) $tag, $tags);
    }
}
