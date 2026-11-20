<?php

namespace App\Campaigning;

use App\Security\User;
use Prospecter\Core\Campaigns\Application\CampaignSheet;
use Prospecter\Core\Campaigns\Application\ConsultCampaign\ConsultCampaign;
use Prospecter\Core\Campaigns\Application\ConsultCampaign\KeptCampaign;
use Prospecter\Core\SharedKernel\Application\QueryHandler;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

/**
 * The campaign of the route, read in the account of the signed-in member, for its voter (ADR 0081): one of another
 * account is unknown there.
 */
final readonly class CampaignResolver implements ValueResolverInterface
{
    public function __construct(private QueryHandler $consultCampaign, private Security $security)
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

    /**
     * The campaign of this id in the account of the member; one of another account refused by the core as unknown.
     */
    public function read(User $member, string $id): CampaignSheet
    {
        $kept = new KeptCampaign();
        ($this->consultCampaign)(new ConsultCampaign($member->memberId(), $id), $kept);

        return $kept->campaign ?? throw new \LogicException('The campaign was not presented');
    }
}
