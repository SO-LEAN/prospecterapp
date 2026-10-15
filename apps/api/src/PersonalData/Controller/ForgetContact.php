<?php

namespace App\PersonalData\Controller;

use App\Security\User;
use App\Shared\CommandBus;
use App\Shared\Resource\ProblemResource;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Prospecter\Core\PersonalData\Application\ForgetContact\ForgetContact as ForgetContactCommand;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag('Contacts')]
#[IsGranted('ROLE_PROSPECTOR')]
final class ForgetContact extends AbstractController
{
    public function __construct(private readonly CommandBus $commands, private readonly ClockInterface $clock)
    {
    }

    #[Route('/api/contacts/{id}', name: 'api_contacts_forget', requirements: ['id' => Requirement::UUID], methods: ['DELETE'])]
    #[OA\Delete(summary: 'Erase a person my organization prospects; the organizations that named them forget them')]
    #[OA\Response(response: 204, description: 'Erased')]
    #[OA\Response(response: 401, description: 'Missing or invalid access token')]
    #[OA\Response(response: 403, description: 'Not a prospector, no right to manage personal data, or a member and not a contact', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 404, description: 'Unknown contact, or one of another account', content: new Model(type: ProblemResource::class))]
    public function __invoke(string $id, #[CurrentUser] User $user): Response
    {
        $this->commands->dispatch(new ForgetContactCommand($user->memberId(), $id, $this->clock->now()));

        return new Response(status: Response::HTTP_NO_CONTENT);
    }
}
