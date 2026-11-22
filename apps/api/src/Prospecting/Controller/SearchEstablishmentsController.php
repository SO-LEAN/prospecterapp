<?php

declare(strict_types=1);

namespace App\Prospecting\Controller;

use App\Prospecting\OrganizationVoter;
use App\Prospecting\Presenter\EstablishmentsPresenter;
use App\Prospecting\Resource\EstablishmentsResource;
use App\Shared\Resource\ProblemResource;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Prospecter\Core\Prospecting\Application\OrganizationSheet;
use Prospecter\Core\Prospecting\Application\SearchEstablishments\SearchEstablishments;
use Prospecter\Core\SharedKernel\Application\QueryHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag('Organizations')]
#[IsGranted('ROLE_PROSPECTOR')]
final class SearchEstablishmentsController extends AbstractController
{
    public function __construct(
        private readonly QueryHandler $searchEstablishments,
    ) {
    }

    #[Route('/api/organizations/{id}/establishments', name: 'api_organizations_establishments', requirements: ['id' => Requirement::UUID], methods: ['GET'])]
    #[IsGranted(OrganizationVoter::ACCESS, subject: 'organization')]
    #[OA\Get(summary: 'The establishments of the directory of the companies the organization may be, to note its SIRET: named like it, those of its municipality first')]
    #[OA\Response(response: 200, description: 'The establishments proposed, none for an organization without corporate name', content: new Model(type: EstablishmentsResource::class))]
    #[OA\Response(response: 401, description: 'Missing or invalid access token')]
    #[OA\Response(response: 403, description: 'The signed-in user is not a prospector', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 404, description: 'Unknown organization, or one that is neither mine nor prospected by mine', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 503, description: 'The directory of the companies does not answer for now (`directoryUnreachable`)', content: new Model(type: ProblemResource::class))]
    public function __invoke(OrganizationSheet $organization): JsonResponse
    {
        $presenter = new EstablishmentsPresenter();
        $this->searchEstablishments->__invoke(new SearchEstablishments($organization->id), $presenter);

        return $this->json($presenter->resource);
    }
}
