<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\Port;

use Prospecter\Core\Prospecting\Application\Exchange\ConsultExchange\ExchangeDetail;
use Prospecter\Core\Prospecting\Domain\Exception\UnknownExchange;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeId;
use Prospecter\Core\Prospecting\Domain\Model\Prospector;

/**
 * Reads an exchange's detail without restoring it (ADR 0088). The detail reads as its reader may read it
 * (`ExchangeReading`). It says what still needs redacting (`ExchangeConformity`). It also says what the exchange's
 * state offers its reader at the moment of reading (`ExchangePossibilities`). When the exchange was just moved, the detail
 * says whether it can move back to the moment it was moved from (`Exchange::isAMomentToCome`).
 */
interface ExchangeDetailRetriever
{
    /**
     * @throws UnknownExchange
     */
    public function get(ExchangeId $id, Prospector $reader, \DateTimeImmutable $asOf, ?\DateTimeImmutable $movedFrom = null): ExchangeDetail;
}
