<?php

declare(strict_types=1);

namespace App\Prospecting\Controller;

use App\Prospecting\OrganizationVoter;
use App\Prospecting\Presenter\EstablishmentsPresenter;
use App\Prospecting\Resource\EstablishmentsResource;
use App\Shared\JsonResponses;
use App\Shared\Resource\ProblemResource;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Prospecter\Core\Prospecting\Application\Organization\SearchEstablishments\SearchEstablishments;
use Prospecter\Core\Prospecting\Application\OrganizationSheet;
use Prospecter\Core\SharedKernel\Application\Handler\QueryHandler;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag('Organizations')]
#[IsGranted('ROLE_PROSPECTOR')]
final readonly class SearchEstablishmentsController
{
    public function __construct(
        private QueryHandler $searchEstablishments,
        private JsonResponses $jsonResponses,
    ) {
    }

    #[Route('/api/organizations/{id}/establishments', name: 'api_organizations_establishments', requirements: ['id' => Requirement::UUID], methods: ['GET'])]
    #[IsGranted(OrganizationVoter::ACCESS, subject: 'organization')]
    #[OA\Get(summary: 'The company directory\'s establishments that the organization may be, to record its SIRET. Their names are like the organization\'s, and those in its municipality come first')]
    #[OA\Response(response: 200, description: 'The proposed establishments. An organization without a corporate name gets none', content: new Model(type: EstablishmentsResource::class))]
    #[OA\Response(response: 401, description: 'Missing or invalid access token')]
    #[OA\Response(response: 403, description: 'The signed-in user is not a prospector', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 404, description: 'Unknown organization, or one that my account neither owns nor prospects', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 503, description: 'The company directory does not answer for now (`directoryUnreachable`)', content: new Model(type: ProblemResource::class))]
    public function __invoke(OrganizationSheet $organization): JsonResponse
    {
        $presenter = new EstablishmentsPresenter();
        $this->searchEstablishments->__invoke(new SearchEstablishments($organization->id), $presenter);

        return $this->jsonResponses->of($presenter->resource);
    }
}
