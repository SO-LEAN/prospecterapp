<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\ConsultExchange;

use Prospecter\Core\Prospecting\Application\Port\ExchangeDetailRetriever;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeId;
use Prospecter\Core\Prospecting\Domain\Model\Prospector;
use Prospecter\Core\Prospecting\Domain\Port\CampaignNames;
use Prospecter\Core\Prospecting\Domain\Port\Prospectors;
use Prospecter\Core\SharedKernel\Application\QueryHandler;
use Prospecter\Core\SharedKernel\Domain\CampaignId;
use Prospecter\Core\SharedKernel\Domain\MemberId;

/**
 * The detail of an exchange, the campaign it was noted in named as Campaigns names it now (ADR 0145).
 */
final readonly class ConsultExchangeHandler implements QueryHandler
{
    public function __construct(
        private ExchangeDetailRetriever $exchangeDetailRetriever,
        private Prospectors $prospectors,
        private CampaignNames $campaignNames,
    ) {
    }

    public function __invoke(ConsultExchange $query, ConsultExchangeOutput $output): void
    {
        $prospector = $this->prospectors->prospectorFrom(new MemberId($query->prospectorId));
        $exchange = $this->exchangeDetailRetriever->get(new ExchangeId($query->id), $prospector, $query->asOf, $query->movedFrom);

        $output->present($this->withItsCampaignNamed($exchange, $prospector));
    }

    private function withItsCampaignNamed(ExchangeDetail $exchange, Prospector $reader): ExchangeDetail
    {
        return null === $exchange->campaignId ? $exchange : $exchange->withCampaignNamed($this->campaignNames->of([new CampaignId($exchange->campaignId)], $reader->accountId));
    }
}
