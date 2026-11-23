<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\WhichContactsHaveExchangesOfCampaign;

use Prospecter\Core\Prospecting\Contract\Service\WhichContactsHaveExchangesOfCampaign\WhichContactsHaveExchangesOfCampaign;
use Prospecter\Core\Prospecting\Contract\Service\WhichContactsHaveExchangesOfCampaign\WhichContactsHaveExchangesOfCampaignOutput;
use Prospecter\Core\Prospecting\Contract\Service\WhichContactsHaveExchangesOfCampaign\WhichContactsHaveExchangesOfCampaignService;
use Prospecter\Core\Prospecting\Domain\Model\CampaignId;
use Prospecter\Core\Prospecting\Domain\Model\ContactId;
use Prospecter\Core\Prospecting\Domain\Repository\ExchangeRepository;
use Prospecter\Core\SharedKernel\Application\QueryHandler;
use Prospecter\Core\SharedKernel\Domain\AccountId;

final readonly class WhichContactsHaveExchangesOfCampaignHandler implements QueryHandler, WhichContactsHaveExchangesOfCampaignService
{
    public function __construct(private ExchangeRepository $exchanges)
    {
    }

    public function __invoke(WhichContactsHaveExchangesOfCampaign $query, WhichContactsHaveExchangesOfCampaignOutput $output): void
    {
        $contacts = array_map(static fn (string $id): ContactId => new ContactId($id), $query->contactIds);
        $exchangedWith = $this->exchanges->contactsWithAnExchangeOf(new AccountId($query->accountId), new CampaignId($query->campaignId), $contacts);

        $output->present(array_map(static fn (ContactId $contact): string => $contact->value, $exchangedWith));
    }
}
