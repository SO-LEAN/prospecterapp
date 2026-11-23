<?php

declare(strict_types=1);

namespace App\Tags\Controller;

use App\Security\User;
use App\Shared\JsonResponses;
use App\Shared\Resource\ProblemResource;
use App\Tags\Resource\SuggestedTagsResource;
use App\Tags\SuggestedTagsPresenter;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Prospecter\Core\Campaigns\Application\ConsultSuggestedTags\ConsultSuggestedTags;
use Prospecter\Core\SharedKernel\Application\Handler\QueryHandler;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag('Tags')]
#[IsGranted('ROLE_PROSPECTOR')]
final readonly class ShowSuggestedTagsController
{
    public function __construct(private QueryHandler $consultSuggestedTags, private JsonResponses $jsonResponses)
    {
    }

    #[Route('/api/tags', name: 'api_tags', methods: ['GET'])]
    #[OA\Get(summary: 'Get the tags proposed to my account: the predefined tags by family, then the tags its organizations and contacts carry')]
    #[OA\Response(response: 200, description: 'The proposed tags', content: new Model(type: SuggestedTagsResource::class))]
    #[OA\Response(response: 401, description: 'Missing or invalid access token')]
    #[OA\Response(response: 403, description: 'Not a prospector', content: new Model(type: ProblemResource::class))]
    public function __invoke(#[CurrentUser] User $user): JsonResponse
    {
        $presenter = new SuggestedTagsPresenter();
        $this->consultSuggestedTags->__invoke(new ConsultSuggestedTags($user->memberId()), $presenter);

        return $this->jsonResponses->of($presenter->resource);
    }
}
