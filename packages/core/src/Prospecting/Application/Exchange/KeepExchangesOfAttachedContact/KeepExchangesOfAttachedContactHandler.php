<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\Exchange\KeepExchangesOfAttachedContact;

use Prospecter\Core\Prospecting\Domain\Model\ContactId;
use Prospecter\Core\Prospecting\Domain\Model\OrganizationId;
use Prospecter\Core\Prospecting\Domain\Repository\ExchangeRepository;
use Prospecter\Core\SharedKernel\Application\Handler\CommandHandler;
use Prospecter\Core\SharedKernel\Application\Handler\PublishesEvents;
use Prospecter\Core\SharedKernel\Application\Port\Events;
use Prospecter\Core\SharedKernel\Domain\Identity\AccountId;
use Prospecter\Core\SharedKernel\Domain\Identity\MemberId;

/**
 * The holder kept some exchanges while the contact waited to be attached. These exchanges follow the contact into
 * the organization they join (ADR 0146). The exchanges already kept for organizations stay as they are (ADR 0074).
 */
final readonly class KeepExchangesOfAttachedContactHandler implements CommandHandler
{
    use PublishesEvents;

    public function __construct(private ExchangeRepository $exchanges, private Events $events)
    {
    }

    public function __invoke(KeepExchangesOfAttachedContact $command): void
    {
        $organization = new OrganizationId($command->organizationId);
        $member = new MemberId($command->memberId);

        foreach ($this->exchanges->withContact(new ContactId($command->contactId), new AccountId($command->accountId)) as $exchange) {
            if (null !== $exchange->contactToAttach()) {
                $exchange->keepFor($organization, by: $member, at: $command->executedAt);
                $this->exchanges->save($exchange);
                $this->publishWhatHappenedTo($exchange);
            }
        }
    }
}
