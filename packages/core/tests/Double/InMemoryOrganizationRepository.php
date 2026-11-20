<?php

declare(strict_types=1);

namespace Tests\Core\Double;

use Prospecter\Core\Prospecting\Domain\Exception\UnknownOrganization;
use Prospecter\Core\Prospecting\Domain\Model\Organization;
use Prospecter\Core\Prospecting\Domain\Model\ResemblanceKey;
use Prospecter\Core\Prospecting\Domain\Model\Website;
use Prospecter\Core\Prospecting\Domain\Repository\OrganizationRepository;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\Email;
use Prospecter\Core\SharedKernel\Domain\ExternalReference;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;
use Prospecter\Core\SharedKernel\Domain\Siret;

final readonly class InMemoryOrganizationRepository implements OrganizationRepository
{
    public function __construct(private InMemoryDatabase $database)
    {
    }

    public function get(OrganizationId $id): Organization
    {
        return $this->database->organization((string) $id) ?? throw UnknownOrganization::identifiedBy($id);
    }

    /**
     * @param list<OrganizationId> $ids
     */
    public function ofIds(array $ids): array
    {
        return array_values(array_filter(array_map(fn (OrganizationId $id): ?Organization => $this->database->organization((string) $id), $ids)));
    }

    public function isEmailUsed(AccountId $accountId, Email $email, OrganizationId $except): bool
    {
        return array_any($this->prospectedBy($accountId), fn (Organization $organization): bool => !$organization->id()->equals($except) && $organization->contactDetails()->email?->value === $email->value);
    }

    public function havingEmails(AccountId $accountId, array $emails): array
    {
        $organizations = [];
        foreach ($this->prospectedBy($accountId) as $organization) {
            $email = $organization->contactDetails()->email;
            if (null !== $email && in_array($email, $emails, false)) {
                $organizations[$email->value] = $organization->id();
            }
        }

        return $organizations;
    }

    public function withSiret(AccountId $accountId, Siret $siret, OrganizationId $except): ?Organization
    {
        return array_find($this->prospectedBy($accountId), fn (Organization $organization): bool => !$organization->id()->equals($except) && $organization->siret()?->value === $siret->value);
    }

    public function withReference(AccountId $accountId, ExternalReference $reference, OrganizationId $except): ?Organization
    {
        return array_find($this->prospectedBy($accountId), fn (Organization $organization): bool => !$organization->id()->equals($except) && $organization->references()->holds($reference));
    }

    public function withWebsiteOfDomain(AccountId $accountId, Website $website, OrganizationId $except): ?Organization
    {
        return array_find($this->prospectedBy($accountId), fn (Organization $organization): bool => !$organization->id()->equals($except) && $organization->website()?->domain() === $website->domain());
    }

    public function resembling(AccountId $accountId, ResemblanceKey $key, OrganizationId $except): array
    {
        $resembling = array_filter($this->prospectedBy($accountId), static fn (Organization $organization): bool => !$organization->id()->equals($except) && $organization->resemblanceKey()?->value === $key->value);

        return array_values(array_map(static fn (Organization $organization): OrganizationId => $organization->id(), $resembling));
    }

    public function save(Organization $organization): void
    {
        $this->database->storeOrganization($organization);
    }

    public function remove(Organization $organization): void
    {
        $this->database->forgetOrganization((string) $organization->id());
    }

    public function subsidiariesOf(OrganizationId $holding): array
    {
        return array_values(array_filter($this->database->organizations(), static fn (Organization $organization): bool => StateOf::the($organization)['holdingId']?->equals($holding) ?? false));
    }

    public function withContacts(array $contacts, AccountId $accountId): array
    {
        return array_values(array_filter($this->prospectedBy($accountId), static fn (Organization $organization): bool => array_any($contacts, $organization->hasContact(...))));
    }

    /**
     * @return list<Organization>
     */
    private function prospectedBy(AccountId $accountId): array
    {
        return array_values(array_filter($this->database->organizations(), static fn (Organization $organization): bool => $organization->isProspectedBy($accountId)));
    }
}
