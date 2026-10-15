<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain;

use Prospecter\Core\Prospecting\Domain\Exception\UnknownOrganization;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\Email;
use Prospecter\Core\SharedKernel\Domain\Exception\EmailAlreadyUsed;
use Prospecter\Core\SharedKernel\Domain\Page;

interface OrganizationRepository
{
    /**
     * @throws UnknownOrganization
     */
    public function get(OrganizationId $id): Organization;

    /**
     * Whether another organization of the prospecting base of the account has this email.
     */
    public function isEmailUsed(AccountId $accountId, Email $email, OrganizationId $except): bool;

    /**
     * @throws EmailAlreadyUsed when another organization took the email meanwhile (EmailUniqueness checked it before)
     */
    public function save(Organization $organization): void;

    /**
     * Newest first; the query matches the corporate name or the email.
     *
     * @return Page<Organization>
     */
    public function pageProspectedBy(AccountId $accountId, string $query, int $page, int $maxByPage): Page;
}
