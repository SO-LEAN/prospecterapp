<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Application\PutTags;

use Prospecter\Core\Campaigns\Domain\Model\Holder;
use Prospecter\Core\Campaigns\Domain\Model\Tag;
use Prospecter\Core\Campaigns\Domain\Model\Tagging;
use Prospecter\Core\Campaigns\Domain\Port\Taggers;
use Prospecter\Core\Campaigns\Domain\Repository\TaggingRepository;
use Prospecter\Core\Campaigns\Domain\Service\TaggableHolders;
use Prospecter\Core\SharedKernel\Application\CommandHandler;
use Prospecter\Core\SharedKernel\Application\Port\Events;
use Prospecter\Core\SharedKernel\Application\PublishesEvents;
use Prospecter\Core\SharedKernel\Domain\Identity\MemberId;

/**
 * A member puts tags on an organization their account prospects, or on one of its contacts.
 */
final readonly class PutTagsHandler implements CommandHandler
{
    use PublishesEvents;

    public function __construct(
        private TaggingRepository $taggings,
        private Taggers $taggers,
        private TaggableHolders $holders,
        private Events $events,
    ) {
    }

    public function __invoke(PutTags $command): void
    {
        $member = new MemberId($command->memberId);
        $accountId = $this->taggers->accountOf($member);
        $holder = Holder::of($command->holder, $command->holderId);
        $this->holders->ensureKnownBy($accountId, $holder);
        $tagging = $this->taggings->find($accountId, $holder) ?? Tagging::of($accountId, $holder, $command->executedAt);
        $tagging->tagWith(array_map(Tag::written(...), $command->tags), by: $member, at: $command->executedAt);
        $this->taggings->save($tagging);
        $this->publishWhatHappenedTo($tagging);
    }
}
