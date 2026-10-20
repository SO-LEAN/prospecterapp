<?php

namespace App\Exchanges;

use App\Exchanges\Resource\ExchangeDetailResource;
use App\Security\User;
use App\Shared\Resource\ProblemResource;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag('Contacts')]
#[IsGranted('ROLE_PROSPECTOR')]
final class ShowExchange extends AbstractController
{
    public function __construct(private readonly ExchangeReader $exchanges)
    {
    }

    #[Route('/api/exchanges/{id}', name: 'api_exchanges_show', requirements: ['id' => Requirement::UUID], methods: ['GET'])]
    #[OA\Get(summary: 'The whole of an exchange with a contact, who noted it and who corrected it last')]
    #[OA\Response(response: 200, description: 'The exchange', content: new Model(type: ExchangeDetailResource::class))]
    #[OA\Response(response: 401, description: 'Missing or invalid access token')]
    #[OA\Response(response: 403, description: 'Not a prospector, or no right to manage personal data', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 404, description: 'Unknown exchange, or one of another account', content: new Model(type: ProblemResource::class))]
    public function __invoke(string $id, #[CurrentUser] User $user): JsonResponse
    {
        return $this->json($this->exchanges->read($user->memberId(), $id));
    }
}
