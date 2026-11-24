<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\Policy;

use Prospecter\Core\PersonalData\Contract\Event\ContactForgotten;
use Prospecter\Core\Prospecting\Application\ForgetFingerprintsOfForgottenContact\ForgetFingerprintsOfForgottenContact;
use Prospecter\Core\SharedKernel\Application\Handler\Policy;

/**
 * When PersonalData forgets a contact, the fingerprints of the contact's exchanges are forgotten (ADR 0166).
 */
final readonly class ForgetFingerprintsOfForgottenContactPolicy implements Policy
{
    public function __invoke(ContactForgotten $event): ForgetFingerprintsOfForgottenContact
    {
        return new ForgetFingerprintsOfForgottenContact(
            accountId: $event->accountId,
            contactId: $event->personId,
            executedAt: $event->occurredAt,
        );
    }
}
