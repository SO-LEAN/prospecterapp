<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\CompleteOrganization;

use Prospecter\Core\Prospecting\Domain\Model\OrganizationSupplement;
use Prospecter\Core\Prospecting\Domain\Repository\OrganizationRepository;
use Prospecter\Core\Prospecting\Domain\Service\EmailUniqueness;
use Prospecter\Core\Prospecting\Domain\Service\ReferenceUniqueness;
use Prospecter\Core\Prospecting\Domain\Service\SiretUniqueness;
use Prospecter\Core\Prospecting\Domain\Service\WebsiteUniqueness;
use Prospecter\Core\SharedKernel\Application\CommandHandler;
use Prospecter\Core\SharedKernel\Application\Port\Events;
use Prospecter\Core\SharedKernel\Application\PublishesEvents;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;

final readonly class CompleteOrganizationHandler implements CommandHandler
{
    use PublishesEvents;

    public function __construct(private OrganizationRepository $organizations, private Events $events, private SiretUniqueness $sirets, private ReferenceUniqueness $references, private WebsiteUniqueness $websites, private EmailUniqueness $emails)
    {
    }

    public function __invoke(CompleteOrganization $command): void
    {
        $organization = $this->organizations->get(new OrganizationId($command->id));
        $organization->completeWith(OrganizationSupplement::given($command->siret, $command->links, $command->website, $command->email, $command->phoneNumber), by: new MemberId($command->prospectorId), at: $command->executedAt);
        $this->sirets->ensureAvailableFor($organization);
        $this->references->ensureAvailableFor($organization);
        $this->websites->ensureAvailableFor($organization);
        $this->emails->ensureAvailableFor($organization);
        $this->organizations->save($organization);
        $this->publishWhatHappenedTo($organization);
    }
}
