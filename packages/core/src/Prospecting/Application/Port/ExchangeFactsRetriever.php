<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\Port;

use Prospecter\Core\Prospecting\Contract\Service\ListExchangeFacts\ExchangeFactsResponse;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeId;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\Slice;

/**
 * Reads the exchanges' facts as the other contexts count them, without restoring the exchanges (ADR 0088). It is the
 * only reading of these facts, for their event and for their service.
 */
interface ExchangeFactsRetriever
{
    /**
     * Returns the exchange's facts as the command is about to write them. They are absent once the exchange is erased.
     */
    public function of(ExchangeId $id): ExchangeFactsResponse;

    /**
     * Returns the facts of the account's exchanges after the cursor, newest first (ADR 0049).
     *
     * @return Slice<ExchangeFactsResponse>
     */
    public function sliceOf(AccountId $accountId, ?string $after, int $max): Slice;
}
