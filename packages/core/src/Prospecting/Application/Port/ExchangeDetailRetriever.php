<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\Port;

use Prospecter\Core\Prospecting\Application\ConsultExchange\ExchangeDetail;
use Prospecter\Core\Prospecting\Domain\Exception\UnknownExchange;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeId;
use Prospecter\Core\Prospecting\Domain\Model\Prospector;

/**
 * Reads the detail of an exchange without restoring it (ADR 0088), as its reader may read it
 * (`ExchangeReading`), with what is to make conform (`ExchangeConformity`) and what its state offers now
 * (`ExchangePossibilities`).
 */
interface ExchangeDetailRetriever
{
    /**
     * @throws UnknownExchange
     */
    public function get(ExchangeId $id, Prospector $reader): ExchangeDetail;
}
