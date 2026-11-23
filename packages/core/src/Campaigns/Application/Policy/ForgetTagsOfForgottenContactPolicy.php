<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Application\Policy;

use Prospecter\Core\Campaigns\Application\ForgetTagsOfForgottenContact\ForgetTagsOfForgottenContact;
use Prospecter\Core\PersonalData\Contract\Event\ContactForgotten;
use Prospecter\Core\SharedKernel\Application\Handler\Policy;

/**
 * When PersonalData forgets a contact, its tags are forgotten with it (ADR 0138).
 */
final readonly class ForgetTagsOfForgottenContactPolicy implements Policy
{
    public function __invoke(ContactForgotten $event): ForgetTagsOfForgottenContact
    {
        return new ForgetTagsOfForgottenContact($event->accountId, $event->personId, $event->occurredAt);
    }
}
