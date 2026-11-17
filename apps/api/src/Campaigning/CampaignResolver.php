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

        $kept = new KeptCampaign();
        ($this->consultCampaign)(new ConsultCampaign($member->memberId(), $request->attributes->getString('id')), $kept);

        return null === $kept->campaign ? [] : [$kept->campaign];
    }
}
