<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Application\ForgetTagsOfForgottenContact;

use Prospecter\Core\Campaigns\Domain\Model\ContactId;
use Prospecter\Core\Campaigns\Domain\Model\Holder;
use Prospecter\Core\Campaigns\Domain\Repository\TaggingRepository;
use Prospecter\Core\PersonalData\Contract\Event\ContactForgotten;
use Prospecter\Core\SharedKernel\Application\EventHandler;
use Prospecter\Core\SharedKernel\Domain\Identity\AccountId;

/**
 * A contact that PersonalData forgot takes its tags with it (ADR 0138). When the event is delivered again, nothing is
 * left to forget.
 */
final readonly class ForgetTagsOfForgottenContactHandler implements EventHandler
{
    public function __construct(private TaggingRepository $taggings)
    {
    }

    public function __invoke(ContactForgotten $event): void
    {
        $tagging = $this->taggings->find(new AccountId($event->accountId), Holder::contact(new ContactId($event->personId)));

        if (null !== $tagging) {
            $this->taggings->remove($tagging);
        }
    }
}
