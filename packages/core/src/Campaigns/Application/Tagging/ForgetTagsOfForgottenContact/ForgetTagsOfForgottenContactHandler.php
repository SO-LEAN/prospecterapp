<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Application\Tagging\ForgetTagsOfForgottenContact;

use Prospecter\Core\Campaigns\Domain\Model\ContactId;
use Prospecter\Core\Campaigns\Domain\Model\Holder;
use Prospecter\Core\Campaigns\Domain\Repository\TaggingRepository;
use Prospecter\Core\SharedKernel\Application\Handler\CommandHandler;
use Prospecter\Core\SharedKernel\Domain\Identity\AccountId;

/**
 * A contact that PersonalData forgot takes its tags with it (ADR 0138). When the command comes again, nothing is left
 * to forget.
 */
final readonly class ForgetTagsOfForgottenContactHandler implements CommandHandler
{
    public function __construct(private TaggingRepository $taggings)
    {
    }

    public function __invoke(ForgetTagsOfForgottenContact $command): void
    {
        $tagging = $this->taggings->find(new AccountId($command->accountId), Holder::contact(new ContactId($command->contactId)));

        if (null !== $tagging) {
            $this->taggings->remove($tagging);
        }
    }
}
