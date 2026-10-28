<?php

declare(strict_types=1);

namespace Prospecter\Core\Accounts\Application\RelocateMyOrganization;

use Prospecter\Core\Accounts\Domain\Repository\AccountRepository;
use Prospecter\Core\Accounts\Domain\Repository\MemberRepository;
use Prospecter\Core\SharedKernel\Application\CommandHandler;
use Prospecter\Core\SharedKernel\Application\GeoLocalizer;
use Prospecter\Core\SharedKernel\Application\Port\Events;
use Prospecter\Core\SharedKernel\Application\PublishesEvents;
use Prospecter\Core\SharedKernel\Domain\Address;
use Prospecter\Core\SharedKernel\Domain\MemberId;

final readonly class RelocateMyOrganizationHandler implements CommandHandler
{
    use PublishesEvents;

    public function __construct(
        private AccountRepository $accounts,
        private MemberRepository $members,
        private Events $events,
        private GeoLocalizer $geoLocalizer,
    ) {
    }

    public function __invoke(RelocateMyOrganization $command): void
    {
        $me = $this->members->get(new MemberId($command->memberId));
        $myAccount = $this->accounts->get($me->accountId());
        $address = new Address($command->street, $command->postalCode, $command->city, $command->country);
        $myAccount->relocateTo($address, $this->geoLocalizer->locate($address), by: $me, at: $command->executedAt);
        $this->accounts->save($myAccount);
        $this->publishWhatHappenedTo($myAccount);
    }
}
