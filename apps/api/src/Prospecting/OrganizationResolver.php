<?php

declare(strict_types=1);

namespace App\Prospecting;

use App\Prospecting\Persistence\OrganizationRevisions;
use Prospecter\Core\Prospecting\Application\ConsultOrganization\ConsultOrganization;
use Prospecter\Core\Prospecting\Application\ConsultOrganization\KeptOrganization;
use Prospecter\Core\Prospecting\Application\OrganizationSheet;
use Prospecter\Core\SharedKernel\Application\Handler\QueryHandler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

/**
 * Resolves the organization that a route names by its `id`, through the core's query. `OrganizationVoter` decides on
 * it before the controller runs (ADR 0081). The core refuses an unknown organization (404).
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
     * Reads the organization with this id for the voter, whatever its account. The core refuses an unknown one.
     */
    public function read(string $id): OrganizationSheet
    {
        $kept = new KeptOrganization();
        $this->revisions->watch($id);
        $this->consultOrganization->__invoke(new ConsultOrganization($id), $kept);

        return $kept->organization;
    }
}
