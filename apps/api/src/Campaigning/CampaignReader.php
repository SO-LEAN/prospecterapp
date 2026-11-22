<?php

declare(strict_types=1);

namespace App\Campaigning;

use App\Campaigning\Resource\CampaignListLinksResource;
use App\Campaigning\Resource\CampaignListResource;
use App\Campaigning\Resource\CampaignResource;
use App\Shared\Links;
use Prospecter\Core\Accounts\Contract\Service\ConsultMembers\ConsultMembers;
use Prospecter\Core\Accounts\Contract\Service\ConsultMembers\ConsultMembersService;
use Prospecter\Core\Accounts\Contract\Service\ConsultMembers\KeptMembers;
use Prospecter\Core\Accounts\Contract\Service\ConsultMembers\MemberResponse;
use Prospecter\Core\Campaigns\Application\CampaignSheet;
use Prospecter\Core\Campaigns\Application\ConsultCampaign\ConsultCampaign;
use Prospecter\Core\Campaigns\Application\ConsultCampaign\KeptCampaign;
use Prospecter\Core\Campaigns\Application\ListCampaigns\KeptCampaigns;
use Prospecter\Core\Campaigns\Application\ListCampaigns\ListCampaigns;
use Prospecter\Core\PersonalData\Application\ConsultPersons\ConsultPersons;
use Prospecter\Core\PersonalData\Application\ConsultPersons\KeptPersons;
use Prospecter\Core\PersonalData\Application\PersonCard;
use Prospecter\Core\SharedKernel\Application\QueryHandler;
use Symfony\Component\Clock\ClockInterface;

/**
 * The campaigns of the team composed of three contexts: Campaigns keeps them and counts their targets, Accounts gives
 * the person of each author, PersonalData names them, in one batch each.
 */
final readonly class CampaignReader
{
    private const string NO_CLOCK = 'UTC';

    public function __construct(
        private QueryHandler $consultCampaign,
        private QueryHandler $listCampaigns,
        private ConsultMembersService $consultMembersService,
        private QueryHandler $consultPersons,
        private CampaignResourceFactory $campaignResourceFactory,
        private Links $links,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @param ?string $timeZone the reader's clock, which the recipients processed today are counted on; UTC otherwise
     */
    public function read(string $reader, string $id, ?string $timeZone = null): CampaignResource
    {
        $kept = new KeptCampaign();
        $this->consultCampaign->__invoke(new ConsultCampaign(memberId: $reader, campaignId: $id, timeZone: $timeZone ?? self::NO_CLOCK, at: $this->clock->now()), $kept);
        $campaign = $kept->campaign ?? throw new \LogicException('The campaign was not presented');

        return $this->campaignResourceFactory->of($campaign, $kept->targeted, $this->authorsOf($reader, [$campaign])[$campaign->authorId] ?? null, $kept->processedToday);
    }

    public function list(string $reader): CampaignListResource
    {
        $kept = new KeptCampaigns();
        $this->listCampaigns->__invoke(new ListCampaigns($reader), $kept);
        $authors = $this->authorsOf($reader, $kept->campaigns);

        return new CampaignListResource(
            array_map(fn (CampaignSheet $campaign): CampaignResource => $this->campaignResourceFactory->of($campaign, $kept->targeted[$campaign->id] ?? 0, $authors[$campaign->authorId] ?? null), $kept->campaigns),
            new CampaignListLinksResource($this->links->to('api_campaigns_list'), $this->links->to('api_campaigns_prepare')),
        );
    }

    /**
     * @param list<CampaignSheet> $campaigns
     *
     * @return array<string, PersonCard> the person of each author PersonalData reads, by member id
     */
    private function authorsOf(string $reader, array $campaigns): array
    {
        $members = new KeptMembers();
        ($this->consultMembersService)(new ConsultMembers(array_values(array_unique(array_map(static fn (CampaignSheet $campaign): string => $campaign->authorId, $campaigns)))), $members);
        $personOfMember = array_map(static fn (MemberResponse $member): string => $member->personId, $members->members);
        $persons = new KeptPersons();
        $this->consultPersons->__invoke(new ConsultPersons($reader, array_values($personOfMember)), $persons);

        return array_filter(array_map(static fn (string $personId): ?PersonCard => $persons->persons[$personId] ?? null, $personOfMember));
    }
}
