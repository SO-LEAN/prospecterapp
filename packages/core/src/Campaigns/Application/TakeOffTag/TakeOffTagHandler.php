<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Application\TakeOffTag;

use Prospecter\Core\Campaigns\Domain\Exception\TagNotCarried;
use Prospecter\Core\Campaigns\Domain\Model\Holder;
use Prospecter\Core\Campaigns\Domain\Model\Tag;
use Prospecter\Core\Campaigns\Domain\Port\Taggers;
use Prospecter\Core\Campaigns\Domain\Repository\TaggingRepository;
use Prospecter\Core\Campaigns\Domain\Service\TaggableHolders;
use Prospecter\Core\SharedKernel\Application\Handler\CommandHandler;
use Prospecter\Core\SharedKernel\Application\Handler\PublishesEvents;
use Prospecter\Core\SharedKernel\Application\Port\Events;
use Prospecter\Core\SharedKernel\Domain\Identity\MemberId;

/**
 * A member takes a tag off an organization or a contact.
 */
final readonly class TakeOffTagHandler implements CommandHandler
{
    use PublishesEvents;

    public function __construct(
        private TaggingRepository $taggings,
        private Taggers $taggers,
        private TaggableHolders $holders,
        private Events $events,
    ) {
    }

    public function __invoke(TakeOffTag $command): void
    {
        $member = new MemberId($command->memberId);
        $accountId = $this->taggers->accountOf($member);
        $holder = Holder::of($command->holder, $command->holderId);
        $this->holders->ensureKnownBy($accountId, $holder);
        $tag = Tag::written($command->tag);
        $tagging = $this->taggings->find($accountId, $holder) ?? throw TagNotCarried::named($tag);
        $tagging->takeOff($tag, by: $member, at: $command->executedAt);
        $this->taggings->save($tagging);
        $this->publishWhatHappenedTo($tagging);
    }
}
