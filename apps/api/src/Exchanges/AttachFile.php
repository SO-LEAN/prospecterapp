<?php

namespace App\Exchanges;

use App\Exchanges\Input\AttachmentInput;
use App\Exchanges\Resource\ExchangeDetailResource;
use App\Security\User;
use App\Shared\CommandBus;
use App\Shared\Identities;
use App\Shared\Resource\ProblemResource;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Prospecter\Core\Prospecting\Application\AttachFile\AttachFile as AttachFileCommand;
use Prospecter\Core\Prospecting\Application\ConsultExchange\ExchangeDetailResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag('Contacts')]
#[IsGranted('ROLE_PROSPECTOR')]
final class AttachFile extends AbstractController
{
    public function __construct(
        private readonly CommandBus $commands,
        private readonly ClockInterface $clock,
        private readonly Identities $identities,
        private readonly ExchangeReader $exchanges,
    ) {
    }

    #[Route('/api/exchanges/{id}/attachments', name: 'api_exchanges_attach_file', requirements: ['id' => Requirement::UUID], methods: ['POST'])]
    #[IsGranted(ExchangeVoter::ATTACH_FILE, subject: 'exchange')]
    #[OA\Post(summary: 'Join to an exchange a file the browser sent with the form of an upload')]
    #[OA\Response(response: 201, description: 'The exchange with the file joined', content: new Model(type: ExchangeDetailResource::class))]
    #[OA\Response(response: 401, description: 'Missing or invalid access token')]
    #[OA\Response(response: 403, description: 'Not a prospector, or no right to manage personal data', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 404, description: 'Unknown exchange, or one of another account; unknown upload, expired or already joined', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 409, description: 'The exchange holds as many files as it may', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 422, description: 'Invalid input, or a file over 10 MB', content: new Model(type: ProblemResource::class))]
    public function __invoke(ExchangeDetailResponse $exchange, #[MapRequestPayload] AttachmentInput $input, #[CurrentUser] User $user): JsonResponse
    {
        $this->commands->dispatch(new AttachFileCommand(
            prospectorId: $user->memberId(),
            exchangeId: $exchange->id,
            id: $this->identities->next(),
            upload: $input->upload,
            name: $input->name,
            executedAt: $this->clock->now(),
        ));

        return $this->json($this->exchanges->read($user->memberId(), $exchange->id), Response::HTTP_CREATED);
    }
}
