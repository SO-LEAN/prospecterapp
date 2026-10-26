<?php

namespace App\Prospecting;

use Prospecter\Core\Prospecting\Application\ConsultOrganization\ConsultOrganization;
use Prospecter\Core\Prospecting\Application\ConsultOrganization\KeptOrganization;
use Prospecter\Core\Prospecting\Application\OrganizationResponse;
use Prospecter\Core\SharedKernel\Application\RefusalTranslatingHandler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

/**
 * The organization a route names by its `id`, read by the query of the core: the subject `OrganizationVoter`
 * decides on before the controller runs (ADR 0081). An unknown one is refused by the core (404).
 */
final readonly class OrganizationResolver implements ValueResolverInterface
{
    public function __construct(private RefusalTranslatingHandler $consultOrganization)
    {
    }

    /**
     * @return list<OrganizationResponse>
     */
    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        if (OrganizationResponse::class !== $argument->getType()) {
            return [];
        }

        $kept = new KeptOrganization();
        ($this->consultOrganization)(new ConsultOrganization($request->attributes->getString('id')), $kept);

        return [$kept->organization];
    }
}
