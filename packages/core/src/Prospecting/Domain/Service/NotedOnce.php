<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Service;

use Prospecter\Core\Prospecting\Domain\Exception\ExchangeAlreadyNoted;
use Prospecter\Core\Prospecting\Domain\Exception\ExchangeErasedBefore;
use Prospecter\Core\Prospecting\Domain\Model\Exchange;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeFingerprint;
use Prospecter\Core\Prospecting\Domain\Model\Fingerprinted;
use Prospecter\Core\Prospecting\Domain\Repository\ExchangeRepository;
use Prospecter\Core\SharedKernel\Domain\AccountId;

/**
 * An exchange is noted once in its account, recognized by its fingerprint whoever noted it first: a rule on the whole
 * base, out of reach of one exchange alone (ADR 0166).
 */
final readonly class NotedOnce
{
    public function __construct(private ExchangeRepository $exchanges)
    {
    }

    /**
     * As an AI or an import notes it.
     *
     * @throws ExchangeAlreadyNoted|ExchangeErasedBefore
     */
    public function ensureFirst(Exchange $exchange): void
    {
        $fingerprint = $exchange->fingerprint();

        if (null !== $fingerprint) {
            $this->ensureFirstAs($exchange->accountId(), $fingerprint);
        }
    }

    /**
     * Before the exchange is noted, when noting it checks first what noting it again would refuse otherwise: the
     * recipient of a campaign, processed by the exchange noted first, is no longer to handle (ADR 0145).
     *
     * @throws ExchangeAlreadyNoted|ExchangeErasedBefore
     */
    public function ensureFirstAs(AccountId $accountId, ExchangeFingerprint $fingerprint): void
    {
        $noted = $this->exchanges->fingerprinted($accountId, $fingerprint);

        if (null !== $noted?->erasure) {
            throw ExchangeErasedBefore::because('Exchange erased before, not noted again', context: ['erasedBy' => $noted->erasure->by->value, 'erasedAt' => $noted->erasure->at->format(\DATE_ATOM)]);
        }

        if (null !== $noted) {
            throw ExchangeAlreadyNoted::because('Exchange already noted', context: ['exchange' => $noted->exchange->value]);
        }
    }

    /**
     * As a member notes it by hand, never refused: noted before, it leaves its fingerprint to the exchange noted first.
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
