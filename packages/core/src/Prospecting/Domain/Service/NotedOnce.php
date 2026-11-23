<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Service;

use Prospecter\Core\Prospecting\Domain\Exception\ExchangeAlreadyNoted;
use Prospecter\Core\Prospecting\Domain\Exception\ExchangeErasedBefore;
use Prospecter\Core\Prospecting\Domain\Model\Exchange;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeFingerprint;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeId;
use Prospecter\Core\Prospecting\Domain\Model\Fingerprinted;
use Prospecter\Core\Prospecting\Domain\Repository\ExchangeRepository;
use Prospecter\Core\SharedKernel\Domain\Identity\AccountId;

/**
 * An account logs an exchange once. Prospecter recognizes it by its fingerprint, whoever logged it first. The rule
 * covers the whole base, out of one exchange's reach (ADR 0166).
 */
final readonly class NotedOnce
{
    public function __construct(private ExchangeRepository $exchanges)
    {
    }

    /**
     * Checks an exchange that an AI or an import logs.
     *
     * @throws ExchangeAlreadyNoted|ExchangeErasedBefore
     */
    public function ensureFirst(Exchange $exchange): void
    {
        $fingerprint = $exchange->fingerprint();
        $noted = null === $fingerprint ? null : $this->keptUnder($exchange->accountId(), $fingerprint);

        if (null !== $noted) {
            throw ExchangeAlreadyNoted::because('This exchange was already logged', context: ['exchange' => $noted->value]);
        }
    }

    /**
     * The exchange logged first under this fingerprint. An AI logs a campaign's sending, and this check comes before
     * Prospecting asks for the campaign's recipient: the exchange may become the recipient's exchange (ADR 0195). An
     * erased exchange is never logged again.
     *
     * @throws ExchangeErasedBefore
     */
    public function keptUnder(AccountId $accountId, ExchangeFingerprint $fingerprint): ?ExchangeId
    {
        $noted = $this->exchanges->fingerprinted($accountId, $fingerprint);

        if (null !== $noted?->erasure) {
            throw ExchangeErasedBefore::because('This exchange was erased, so it is not logged again', context: ['erasedBy' => $noted->erasure->by->value, 'erasedAt' => $noted->erasure->at->format(\DATE_ATOM)]);
        }

        return $noted?->exchange;
    }

    /**
     * Checks an exchange that a member logs by hand, and never refuses it. When the exchange was logged before, it
     * leaves its fingerprint to the exchange logged first.
     */
    public function fingerprintUnlessNotedBefore(Exchange $exchange): void
    {
        if (null !== $this->notedBefore($exchange)) {
            $exchange->leaveItsFingerprint();
        }
    }

    private function notedBefore(Exchange $exchange): ?Fingerprinted
    {
        $fingerprint = $exchange->fingerprint();

        return null === $fingerprint ? null : $this->exchanges->fingerprinted($exchange->accountId(), $fingerprint);
    }
}
