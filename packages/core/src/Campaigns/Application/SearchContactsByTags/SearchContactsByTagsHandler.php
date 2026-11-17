<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Application\SearchContactsByTags;

use Prospecter\Core\Campaigns\Application\Port\ContactTagSources;
use Prospecter\Core\Campaigns\Application\Port\TaggedContactsRetriever;
use Prospecter\Core\Campaigns\Domain\Model\TagSelection;
use Prospecter\Core\Campaigns\Domain\Port\Taggers;
use Prospecter\Core\SharedKernel\Application\QueryHandler;
use Prospecter\Core\SharedKernel\Domain\MemberId;

/**
 * The contacts of the account of the member carrying every tag chosen, their own, inherited or automatic: every
 * prospector searches them, tags being no personal data.
 */
final readonly class SearchContactsByTagsHandler implements QueryHandler
{
    public function __construct(
        private TaggedContactsRetriever $taggedContactsRetriever,
        private Taggers $taggers,
    ) {
    }

    public function __invoke(SearchContactsByTags $query, SearchContactsByTagsOutput $output): void
    {
        $selection = TagSelection::of($query->tags);
        $accountId = $this->taggers->accountOf(new MemberId($query->memberId));
        $carrying = array_filter($this->taggedContactsRetriever->sourcesOfEveryContact($accountId), static fn (ContactTagSources $contact): bool => $contact->tags()->carriesAll($selection));

        $output->present(self::sliceOf(array_column($carrying, 'contactId'), $query->after, $query->max));
    }

    /**
     * @param list<string> $contactIds
     */
    private static function sliceOf(array $contactIds, ?string $after, int $max): TaggedContactPage
    {
        rsort($contactIds, SORT_STRING);
        $remaining = array_values(array_filter($contactIds, static fn (string $contactId): bool => null === $after || strcmp($contactId, $after) < 0));
        $slice = array_slice($remaining, 0, $max);

        return new TaggedContactPage($slice, count($contactIds), count($remaining) > $max ? array_last($slice) : null);
    }
}
