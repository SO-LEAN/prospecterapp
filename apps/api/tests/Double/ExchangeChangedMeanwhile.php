<?php

namespace Tests\App\Double;

use App\Prospecting\Persistence\DynamoExchangeRepository;
use App\Shared\Identities;
use App\Shared\Messaging\CommitOutbox;
use App\Shared\Persistence\DynamoUnitOfWork;
use App\Shared\Persistence\Hydrator;
use App\Shared\Persistence\RevisionsRead;
use App\Shared\Persistence\Table;
use Aws\DynamoDb\DynamoDbClient;
use Aws\DynamoDb\Marshaler;
use Prospecter\Core\Prospecting\Domain\Model\Exchange;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeFingerprint;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeId;
use Prospecter\Core\Prospecting\Domain\Model\Fingerprinted;
use Prospecter\Core\Prospecting\Domain\Repository\ExchangeRepository;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;
use Prospecter\Core\SharedKernel\Domain\PersonId;
use Symfony\Component\Clock\ClockInterface;

/**
 * The repository of the exchanges, where, once a test asks it to, another request corrects the text of an exchange
 * just before it is saved, after the command read it: the race the lock of its revision catches (ADR 0181). One text
 * per save, in the order given; it writes through the real one otherwise.
 */
final class ExchangeChangedMeanwhile implements ExchangeRepository
{
    /** @var list<string> */
    private array $textsMeanwhile = [];

    public function __construct(
        private readonly ExchangeRepository $exchanges,
        private readonly DynamoDbClient $client,
        private readonly Marshaler $marshaler,
        private readonly Table $prospectingTable,
        private readonly Identities $identities,
        private readonly ClockInterface $commitClock,
    ) {
    }

    public function correctTextMeanwhile(string ...$texts): void
    {
        $this->textsMeanwhile = array_values($texts);
    }

    public function get(ExchangeId $id): Exchange
    {
        return $this->exchanges->get($id);
    }

    public function save(Exchange $exchange): void
    {
        $this->anotherRequestCorrectsTheTextOf($exchange->id());
        $this->exchanges->save($exchange);
    }

    public function remove(Exchange $exchange): void
    {
        $this->exchanges->remove($exchange);
    }

    public function fingerprinted(AccountId $accountId, ExchangeFingerprint $fingerprint): ?Fingerprinted
    {
        return $this->exchanges->fingerprinted($accountId, $fingerprint);
    }

    public function forgetFingerprintsOf(AccountId $accountId, PersonId $contact): void
    {
        $this->exchanges->forgetFingerprintsOf($accountId, $contact);
    }

    public function keptFor(OrganizationId $organization): array
    {
        return $this->exchanges->keptFor($organization);
    }

    public function withContact(PersonId $contact, AccountId $accountId): array
    {
        return $this->exchanges->withContact($contact, $accountId);
    }

    private function anotherRequestCorrectsTheTextOf(ExchangeId $id): void
    {
        $text = array_shift($this->textsMeanwhile);

        if (null === $text) {
            return;
        }

        $exchanges = $this->anotherRequest();
        $exchange = $exchanges->get($id);
        $exchange->correctText($text, by: Hydrator::stateOf($exchange)['createdBy'], at: $this->commitClock->now());
        $exchanges->save($exchange);
    }

    private function anotherRequest(): DynamoExchangeRepository
    {
        return new DynamoExchangeRepository(new Table($this->client, $this->marshaler, new DynamoUnitOfWork($this->client, new CommitOutbox([], $this->marshaler, $this->identities, $this->commitClock)), new RevisionsRead(), $this->prospectingTable->name));
    }
}
