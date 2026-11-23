<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\ForgetFingerprintsOfForgottenContact;

use Prospecter\Core\PersonalData\Contract\Event\ContactForgotten;
use Prospecter\Core\Prospecting\Domain\Model\ContactId;
use Prospecter\Core\Prospecting\Domain\Repository\ExchangeRepository;
use Prospecter\Core\SharedKernel\Application\EventHandler;
use Prospecter\Core\SharedKernel\Domain\Identity\AccountId;

/**
 * Once a contact is forgotten, Prospecter no longer keeps the fingerprints of their exchanges, erased or not.
 * It forgets them too (ADR 0166).
 */
final readonly class ForgetFingerprintsOfForgottenContactHandler implements EventHandler
{
    public function __construct(private ExchangeRepository $exchanges)
    {
    }

    public function __invoke(ContactForgotten $event): void
    {
        $this->exchanges->forgetFingerprintsOf(new AccountId($event->accountId), new ContactId($event->personId));
    }
}
