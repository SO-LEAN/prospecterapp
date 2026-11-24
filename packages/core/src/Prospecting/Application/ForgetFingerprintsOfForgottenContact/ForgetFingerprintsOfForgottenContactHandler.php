<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\ForgetFingerprintsOfForgottenContact;

use Prospecter\Core\Prospecting\Domain\Model\ContactId;
use Prospecter\Core\Prospecting\Domain\Repository\ExchangeRepository;
use Prospecter\Core\SharedKernel\Application\Handler\CommandHandler;
use Prospecter\Core\SharedKernel\Domain\Identity\AccountId;

/**
 * Once a contact is forgotten, Prospecter no longer keeps the fingerprints of their exchanges, erased or not.
 * It forgets them too (ADR 0166).
 */
final readonly class ForgetFingerprintsOfForgottenContactHandler implements CommandHandler
{
    public function __construct(private ExchangeRepository $exchanges)
    {
    }

    public function __invoke(ForgetFingerprintsOfForgottenContact $command): void
    {
        $this->exchanges->forgetFingerprintsOf(new AccountId($command->accountId), new ContactId($command->contactId));
    }
}
