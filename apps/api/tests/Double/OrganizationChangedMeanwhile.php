<?php

declare(strict_types=1);

namespace Tests\App\Double;

use App\Prospecting\Persistence\DynamoOrganizationRepository;
use App\Shared\Identities;
use App\Shared\Messaging\CommitOutbox;
use App\Shared\Persistence\DynamoUnitOfWork;
use App\Shared\Persistence\RevisionsRead;
use App\Shared\Persistence\Table;
use Aws\DynamoDb\DynamoDbClient;
use Aws\DynamoDb\Marshaler;
use Prospecter\Core\Prospecting\Domain\Model\Organization;
use Prospecter\Core\Prospecting\Domain\Model\OrganizationId;
use Prospecter\Core\Prospecting\Domain\Model\ResemblanceKey;
use Prospecter\Core\Prospecting\Domain\Model\Website;
use Prospecter\Core\Prospecting\Domain\Repository\OrganizationRepository;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\Email;
use Prospecter\Core\SharedKernel\Domain\ExternalReference;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\PersonId;
use Prospecter\Core\SharedKernel\Domain\Siret;
use Symfony\Component\Clock\ClockInterface;

/**
 * The repository of the organizations, where, once a test asks it to, another request attaches a contact to an
 * organization just before it is saved or removed, after the command read it: the race the lock of its revision
 * catches (ADR 0181). One contact per write, in the order given; it writes through the real one otherwise.
 */
final class OrganizationChangedMeanwhile implements OrganizationRepository
{
    /** @var list<PersonId> */
    private array $attachedMeanwhile = [];

    public function __construct(
        private readonly OrganizationRepository $organizations,
        private readonly DynamoDbClient $client,
        private readonly Marshaler $marshaler,
        private readonly Table $prospectingTable,
        private readonly Identities $identities,
        private readonly ClockInterface $commitClock,
    ) {
    }

    public function attachMeanwhile(PersonId ...$contacts): void
    {
        $this->attachedMeanwhile = array_values($contacts);
    }

    public function get(OrganizationId $id): Organization
    {
        return $this->organizations->get($id);
    }

    public function ofIds(array $ids): array
    {
        return $this->organizations->ofIds($ids);
    }

    public function isEmailUsed(AccountId $accountId, Email $email, OrganizationId $except): bool
    {
        return $this->organizations->isEmailUsed($accountId, $email, $except);
    }

    public function havingEmails(AccountId $accountId, array $emails): array
    {
        return $this->organizations->havingEmails($accountId, $emails);
    }

    public function withSiret(AccountId $accountId, Siret $siret, OrganizationId $except): ?Organization
    {
        return $this->organizations->withSiret($accountId, $siret, $except);
    }

    public function withReference(AccountId $accountId, ExternalReference $reference, OrganizationId $except): ?Organization
    {
        return $this->organizations->withReference($accountId, $reference, $except);
    }

    public function withWebsiteOfDomain(AccountId $accountId, Website $website, OrganizationId $except): ?Organization
    {
        return $this->organizations->withWebsiteOfDomain($accountId, $website, $except);
    }

    public function resembling(AccountId $accountId, ResemblanceKey $key, OrganizationId $except): array
    {
        return $this->organizations->resembling($accountId, $key, $except);
    }

    public function save(Organization $organization): void
    {
        $this->anotherRequestAttachesTheNextContactTo($organization->id());
        $this->organizations->save($organization);
    }

    public function remove(Organization $organization): void
    {
        $this->anotherRequestAttachesTheNextContactTo($organization->id());
        $this->organizations->remove($organization);
    }

    public function subsidiariesOf(OrganizationId $holding): array
    {
        return $this->organizations->subsidiariesOf($holding);
    }

    public function withContacts(array $contacts, AccountId $accountId): array
    {
        return $this->organizations->withContacts($contacts, $accountId);
    }

    private function anotherRequestAttachesTheNextContactTo(OrganizationId $id): void
    {
        $contact = array_shift($this->attachedMeanwhile);

        if (null === $contact) {
            return;
        }

        $organizations = $this->anotherRequest();
        $organization = $organizations->get($id);
        $organization->addContact($contact, by: new MemberId($this->identities->next()), at: $this->commitClock->now());
        $organizations->save($organization);
    }

    private function anotherRequest(): DynamoOrganizationRepository
    {
        return new DynamoOrganizationRepository(new Table($this->client, $this->marshaler, new DynamoUnitOfWork($this->client, new CommitOutbox([], $this->marshaler, $this->identities, $this->commitClock)), new RevisionsRead(), $this->prospectingTable->name));
    }
}
