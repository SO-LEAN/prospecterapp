<?php

declare(strict_types=1);

namespace Tests\Core\Double;

use Prospecter\Core\Prospecting\Domain\Exception\UnknownExchange;
use Prospecter\Core\Prospecting\Domain\Model\Exchange;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeFingerprint;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeId;
use Prospecter\Core\Prospecting\Domain\Model\Fingerprinted;
use Prospecter\Core\Prospecting\Domain\Repository\ExchangeRepository;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

final readonly class InMemoryExchangeRepository implements ExchangeRepository
{
    public function __construct(private InMemoryDatabase $database)
    {
    }

    public function get(ExchangeId $id): Exchange
    {
        return $this->database->exchange((string) $id) ?? throw UnknownExchange::identifiedBy($id);
    }

    public function save(Exchange $exchange): void
    {
        $this->database->storeExchange($exchange);
    }

    public function remove(Exchange $exchange): void
    {
        $this->database->forgetExchange((string) $exchange->id());
        $this->database->eraseFingerprintOf($exchange);
    }

    public function wasImported(ExchangeId $id): bool
    {
        return $this->database->wasImported((string) $id);
    }

    public function fingerprinted(AccountId $accountId, ExchangeFingerprint $fingerprint): ?Fingerprinted
    {
        return $this->database->fingerprinted($accountId, $fingerprint);
    }

    public function keptFor(OrganizationId $organization): array
    {
        return array_values(array_filter($this->database->exchanges(), static fn (Exchange $exchange): bool => array_any(StateOf::the($exchange)['keptFor'], $organization->equals(...))));
    }

    public function withContact(PersonId $contact, AccountId $accountId): array
    {
        return array_values(array_filter($this->database->exchanges(), static fn (Exchange $exchange): bool => true === StateOf::the($exchange)['contact']?->equals($contact) && $exchange->accountId()->equals($accountId)));
    }
}
