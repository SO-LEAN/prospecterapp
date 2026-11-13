<?php

namespace App\Me\Controller;

use App\Me\Input\RemindersInput;
use App\Me\MyRemindersReader;
use App\Me\Resource\RemindersResource;
use App\Security\User;
use App\Shared\CommandBus;
use App\Shared\Resource\ProblemResource;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Prospecter\Core\Accounts\Application\ChangeMyReminders\ChangeMyReminders;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag('My account')]
#[IsGranted('ROLE_USER')]
final class ChangeMyRemindersController extends AbstractController
{
    public function __construct(
        private readonly CommandBus $commandBus,
        private readonly ClockInterface $clock,
        private readonly MyRemindersReader $myRemindersReader,
    ) {
    }

    #[Route('/api/me/reminders', name: 'api_me_change_reminders', methods: ['PATCH'])]
    #[OA\Patch(summary: 'Turn my reminders of the exchanges I planned on or off, and choose how long before they come: 0, 5, 10, 15, 30 or 60 minutes')]
    #[OA\Response(response: 200, description: 'My reminders', content: new Model(type: RemindersResource::class))]
    #[OA\Response(response: 401, description: 'Missing or invalid access token')]
    #[OA\Response(response: 403, description: 'The signed-in user is not a user', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 422, description: 'A delay not offered', content: new Model(type: ProblemResource::class))]
    public function __invoke(#[MapRequestPayload] RemindersInput $input, #[CurrentUser] User $user): JsonResponse
    {
        $this->commandBus->dispatch(new ChangeMyReminders(
            memberId: $user->memberId(),
            enabled: $input->enabled ?? throw new \LogicException('Reminders validated are on or off'),
            minutesBefore: $input->minutesBefore ?? throw new \LogicException('Reminders validated have their delay'),
            executedAt: $this->clock->now(),
        ));

        return $this->json($this->myRemindersReader->read($user->memberId()));
    }
}
