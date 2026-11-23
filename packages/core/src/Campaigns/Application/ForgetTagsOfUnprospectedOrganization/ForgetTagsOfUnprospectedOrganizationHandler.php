<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Application\ForgetTagsOfUnprospectedOrganization;

use Prospecter\Core\Campaigns\Domain\Model\Holder;
use Prospecter\Core\Campaigns\Domain\Repository\TaggingRepository;
use Prospecter\Core\Prospecting\Contract\Event\OrganizationFactsChanged;
use Prospecter\Core\SharedKernel\Application\EventHandler;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;

/**
 * An organization that the account no longer prospects takes its tags with it (ADR 0138). The handler reacts to its
 * terminal fact: the subscription passes only those. When the event is delivered again, nothing is left to forget.
 */
final readonly class ForgetTagsOfUnprospectedOrganizationHandler implements EventHandler
{
    public function __construct(private TaggingRepository $taggings)
    {
    }

    public function __invoke(OrganizationFactsChanged $event): void
    {
        if ($event->present) {
            return;
        }

        $tagging = $this->taggings->find(new AccountId($event->accountId), Holder::organization(new OrganizationId($event->organizationId)));

        if (null !== $tagging) {
            $this->taggings->remove($tagging);
        }
    }
}
