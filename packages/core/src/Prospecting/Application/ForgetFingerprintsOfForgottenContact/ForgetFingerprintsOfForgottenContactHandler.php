<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\ForgetFingerprintsOfForgottenContact;

use Prospecter\Core\PersonalData\Contract\Event\ContactForgotten;
use Prospecter\Core\Prospecting\Domain\Repository\ExchangeRepository;
use Prospecter\Core\SharedKernel\Application\EventHandler;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

/**
 * A contact forgotten, the fingerprints of their exchanges, erased or not, are no data to keep any more: they are
 * forgotten too (ADR 0166).
 */
final readonly class ForgetFingerprintsOfForgottenContactHandler implements EventHandler
{
    public function __construct(private ExchangeRepository $exchanges)
    {
    }

    public function __invoke(ContactForgotten $event): void
    {
        $this->exchanges->forgetFingerprintsOf(new AccountId($event->accountId), new PersonId($event->personId));
    }
}
