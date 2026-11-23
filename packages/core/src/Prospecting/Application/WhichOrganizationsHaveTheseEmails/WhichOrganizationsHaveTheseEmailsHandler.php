<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\WhichOrganizationsHaveTheseEmails;

use Prospecter\Core\Prospecting\Contract\Service\WhichOrganizationsHaveTheseEmails\WhichOrganizationsHaveTheseEmails;
use Prospecter\Core\Prospecting\Contract\Service\WhichOrganizationsHaveTheseEmails\WhichOrganizationsHaveTheseEmailsOutput;
use Prospecter\Core\Prospecting\Contract\Service\WhichOrganizationsHaveTheseEmails\WhichOrganizationsHaveTheseEmailsService;
use Prospecter\Core\Prospecting\Domain\Model\OrganizationId;
use Prospecter\Core\Prospecting\Domain\Repository\OrganizationRepository;
use Prospecter\Core\SharedKernel\Application\QueryHandler;
use Prospecter\Core\SharedKernel\Domain\Contact\Email;
use Prospecter\Core\SharedKernel\Domain\Identity\AccountId;

final readonly class WhichOrganizationsHaveTheseEmailsHandler implements QueryHandler, WhichOrganizationsHaveTheseEmailsService
{
    public function __construct(private OrganizationRepository $organizations)
    {
    }

    public function __invoke(WhichOrganizationsHaveTheseEmails $query, WhichOrganizationsHaveTheseEmailsOutput $output): void
    {
        $emails = array_map(static fn (string $email): Email => new Email($email), $query->emails);
        $organizations = $this->organizations->havingEmails(new AccountId($query->accountId), $emails);

        $output->present(array_map(static fn (OrganizationId $organization): string => $organization->value, $organizations));
    }
}
