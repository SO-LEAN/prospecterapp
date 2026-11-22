<?php

namespace App\Prospecting;

use App\Prospecting\Persistence\OrganizationRevisions;
use Prospecter\Core\Prospecting\Application\ConsultOrganization\ConsultOrganization;
use Prospecter\Core\Prospecting\Application\ConsultOrganization\KeptOrganization;
use Prospecter\Core\Prospecting\Application\OrganizationSheet;
use Prospecter\Core\SharedKernel\Application\QueryHandler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

/**
 * The organization a route names by its `id`, read by the query of the core: the subject `OrganizationVoter`
 * decides on before the controller runs (ADR 0081). An unknown one is refused by the core (404).
 */
final readonly class OrganizationResolver implements ValueResolverInterface
{
    public function __construct(private QueryHandler $consultOrganization, private OrganizationRevisions $revisions)
    {
    }

    /**
     * @return list<OrganizationSheet>
     */
    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        if (OrganizationSheet::class !== $argument->getType()) {
            return [];
        }

        return [$this->read($request->attributes->getString('id'))];
    }

    /**
     * The organization of this id, for the voter, whatever its account; an unknown one refused by the core.
     */
    public function read(string $id): OrganizationSheet
    {
        $kept = new KeptOrganization();
        $this->revisions->watch($id);
        ($this->consultOrganization)(new ConsultOrganization($id), $kept);

        return $kept->organization;
    }
}
