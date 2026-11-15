<?php

namespace App\Contacts\Controller;

use App\Contacts\AgendaReader;
use App\Contacts\Resource\ExchangesToProcessResource;
use App\Security\User;
use App\Shared\Resource\ProblemResource;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag('My account')]
#[IsGranted('ROLE_PROSPECTOR')]
final class ShowExchangesToProcessController extends AbstractController
{
    public function __construct(
        private readonly AgendaReader $agendaReader,
        private readonly ClockInterface $clock,
    ) {
    }

    #[Route('/api/me/exchanges-to-process', name: 'api_me_exchanges_to_process', methods: ['GET'])]
    #[OA\Get(summary: 'The exchanges the signed-in member planned whose end has passed, not yet marked done, missed nor cancelled')]
    #[OA\Response(response: 200, description: 'The exchanges to process, the oldest first, each with the links handling it', content: new Model(type: ExchangesToProcessResource::class))]
    #[OA\Response(response: 401, description: 'Missing or invalid access token')]
    #[OA\Response(response: 403, description: 'Not a prospector', content: new Model(type: ProblemResource::class))]
    public function __invoke(#[CurrentUser] User $user): JsonResponse
    {
        return $this->json($this->agendaReader->readExchangesToProcess($user->memberId(), $this->clock->now()));
    }
}
