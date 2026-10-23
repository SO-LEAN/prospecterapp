<?php

namespace App\Exchanges;

use App\Exchanges\Input\TextCorrectionInput;
use App\Exchanges\Resource\ExchangeDetailResource;
use App\Security\User;
use App\Shared\CommandBus;
use App\Shared\Resource\ProblemResource;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Prospecter\Core\Prospecting\Application\CorrectExchangeText\CorrectExchangeText as CorrectExchangeTextCommand;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag('Contacts')]
#[IsGranted('ROLE_PROSPECTOR')]
final class CorrectExchangeText extends AbstractController
{
    public function __construct(
        private readonly CommandBus $commands,
        private readonly ClockInterface $clock,
        private readonly ExchangeReader $exchanges,
    ) {
    }

    #[Route('/api/exchanges/{id}/text', name: 'api_exchanges_correct_text', requirements: ['id' => Requirement::UUID], methods: ['PATCH'])]
    #[OA\Patch(summary: 'Correct the text of an exchange only, to make it conform, and declare it made conform once its contact was forgotten; open to every prospector of the account')]
    #[OA\Response(response: 200, description: 'The corrected exchange', content: new Model(type: ExchangeDetailResource::class))]
    #[OA\Response(response: 401, description: 'Missing or invalid access token')]
    #[OA\Response(response: 403, description: 'Not a prospector', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 404, description: 'Unknown exchange, or one of another account', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 409, description: 'Declared made conform while nothing is to make conform', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 422, description: 'Invalid text: too long, or another fact of the exchange', content: new Model(type: ProblemResource::class))]
    public function __invoke(string $id, #[MapRequestPayload] TextCorrectionInput $input, #[CurrentUser] User $user): JsonResponse
    {
        $this->commands->dispatch(new CorrectExchangeTextCommand($user->memberId(), $id, $input->text, $this->clock->now(), $input->madeConform));

        return $this->json($this->exchanges->read($user->memberId(), $id));
    }
}
