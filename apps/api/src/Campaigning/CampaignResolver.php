<?php

declare(strict_types=1);

namespace App\Campaigning;

use App\Security\User;
use Prospecter\Core\Campaigns\Application\CampaignSheet;
use Prospecter\Core\Campaigns\Application\ConsultCampaign\ConsultCampaign;
use Prospecter\Core\Campaigns\Application\ConsultCampaign\KeptCampaign;
use Prospecter\Core\SharedKernel\Application\QueryHandler;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

/**
 * Reads the route's campaign in the signed-in member's account, for its voter (ADR 0081). Another account's campaign is
 * unknown there. The voter reads none of the day's figures, so any clock will do.
 */
final readonly class CampaignResolver implements ValueResolverInterface
{
    private const string ANY_CLOCK = 'UTC';

    public function __construct(private QueryHandler $consultCampaign, private Security $security, private ClockInterface $clock)
    {
    }

    /**
     * @return list<CampaignSheet>
     */
    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        $member = $this->security->getUser();

        if (CampaignSheet::class !== $argument->getType() || !$member instanceof User) {
            return [];
        }

        return [$this->read($member, $request->attributes->getString('id'))];
    }

    /** The campaign with this id in the member's account. The core refuses another account's campaign as unknown. */
    public function read(User $member, string $id): CampaignSheet
    {
        $kept = new KeptCampaign();
        $this->consultCampaign->__invoke(new ConsultCampaign(memberId: $member->memberId(), campaignId: $id, timeZone: self::ANY_CLOCK, at: $this->clock->now()), $kept);

        return $kept->campaign ?? throw new \LogicException('The campaign was not presented');
    }
}
