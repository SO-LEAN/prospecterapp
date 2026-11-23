<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Application\Policy;

use Prospecter\Core\Campaigns\Application\ForgetRecipientOfForgottenContact\ForgetRecipientOfForgottenContact;
use Prospecter\Core\PersonalData\Contract\Event\ContactForgotten;
use Prospecter\Core\SharedKernel\Application\Handler\Policy;

/**
 * When PersonalData forgets a contact, the contact leaves the campaigns that keep it as a recipient.
 */
final readonly class ForgetRecipientOfForgottenContactPolicy implements Policy
{
    public function __invoke(ContactForgotten $event): ForgetRecipientOfForgottenContact
    {
        return new ForgetRecipientOfForgottenContact($event->accountId, $event->personId, $event->occurredAt);
    }
}
