<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\RecognizeCallingOrganization;

use Prospecter\Core\Prospecting\Application\OrganizationResponse;
use Prospecter\Core\Prospecting\Domain\Port\Prospectors;
use Prospecter\Core\Prospecting\Domain\Repository\OrganizationRepository;
use Prospecter\Core\SharedKernel\Application\QueryHandler;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\PhoneNumber;

final readonly class RecognizeCallingOrganizationHandler implements QueryHandler
{
    public function __construct(private OrganizationRepository $organizations, private Prospectors $prospectors)
    {
    }

    public function __invoke(RecognizeCallingOrganization $query, RecognizeCallingOrganizationOutput $output): void
    {
        $prospector = $this->prospectors->prospectorFrom(new MemberId($query->prospectorId));
        $organizations = $this->organizations->withPhoneNumber($prospector->accountId, PhoneNumber::of($query->phoneNumber));

        $output->present(array_map(OrganizationResponse::of(...), $organizations));
    }
}
