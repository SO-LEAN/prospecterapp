<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Application\Tagging\ForgetTagsOfUnprospectedOrganization;

use Prospecter\Core\Campaigns\Domain\Model\Holder;
use Prospecter\Core\Campaigns\Domain\Model\OrganizationId;
use Prospecter\Core\Campaigns\Domain\Repository\TaggingRepository;
use Prospecter\Core\SharedKernel\Application\Handler\CommandHandler;
use Prospecter\Core\SharedKernel\Domain\Identity\AccountId;

/**
 * An organization that the account no longer prospects takes its tags with it (ADR 0138). When the command comes
 * again, nothing is left to forget.
 */
final readonly class ForgetTagsOfUnprospectedOrganizationHandler implements CommandHandler
{
    public function __construct(private TaggingRepository $taggings)
    {
    }

    public function __invoke(ForgetTagsOfUnprospectedOrganization $command): void
    {
        $tagging = $this->taggings->find(new AccountId($command->accountId), Holder::organization(new OrganizationId($command->organizationId)));

        if (null !== $tagging) {
            $this->taggings->remove($tagging);
        }
    }
}
