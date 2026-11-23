<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Application\ConsultContactTags;

use Prospecter\Core\Campaigns\Application\Port\ContactTagSources;
use Prospecter\Core\Campaigns\Application\Port\ContactTagsRetriever;
use Prospecter\Core\Campaigns\Domain\Model\ContactId;
use Prospecter\Core\Campaigns\Domain\Model\InheritedTag;
use Prospecter\Core\Campaigns\Domain\Model\OrganizationId;
use Prospecter\Core\Campaigns\Domain\Model\Tag;
use Prospecter\Core\Campaigns\Domain\Port\Taggers;
use Prospecter\Core\Campaigns\Domain\Service\TaggableHolders;
use Prospecter\Core\SharedKernel\Application\QueryHandler;
use Prospecter\Core\SharedKernel\Domain\MemberId;

/**
 * Every tag that contacts of the member's account carry: their own, inherited and automatic tags (ADR 0141). A person
 * who is not a contact of the account is unknown.
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
        $contacts = array_map(static fn (string $contact): ContactId => new ContactId($contact), $query->contactIds);
        $this->holders->ensureContactsOf($accountId, $contacts);
        $output->present(array_map(self::responseOf(...), $this->contactTagsRetriever->sourcesOf($accountId, $contacts)));
    }

    private static function responseOf(ContactTagSources $sources): ContactTagsResponse
    {
        $tags = $sources->tags();

        return new ContactTagsResponse(
            $sources->contactId,
            self::written($tags->own),
            array_map(static fn (InheritedTag $inherited): InheritedTagResponse => new InheritedTagResponse((string) $inherited->tag, array_map(static fn (OrganizationId $organization): string => $organization->value, $inherited->from)), $tags->inherited),
            self::written($tags->automatic),
        );
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
