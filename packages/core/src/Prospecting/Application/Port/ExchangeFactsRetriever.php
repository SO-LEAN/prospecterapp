<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\Port;

use Prospecter\Core\Prospecting\Contract\Service\ListExchangeFacts\ExchangeFactsResponse;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeId;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\Slice;

/**
 * Reads the facts of the exchanges as the other contexts count them, without restoring them (ADR 0088): the one
 * reading of them, for their event and for their service.
 */
interface ExchangeFactsRetriever
{
    /**
     * The facts of the exchange as the command is about to write them, absent once it is erased.
     */
    public function of(ExchangeId $id): ExchangeFactsResponse;

    /**
     * The facts of the exchanges of the account newest first, after the cursor (ADR 0049).
     *
     * @return Slice<ExchangeFactsResponse>
     */
    public function sliceOf(AccountId $accountId, ?string $after, int $max): Slice;
}
