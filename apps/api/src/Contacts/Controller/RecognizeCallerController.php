<?php

declare(strict_types=1);

namespace App\Contacts\Controller;

use App\Contacts\CallerReader;
use App\Contacts\Resource\CallerResource;
use App\Security\User;
use App\Shared\Resource\ProblemResource;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Who calls (ADR 0072), composed by `CallerReader`.
 */
#[OA\Tag('Contacts')]
#[IsGranted('ROLE_PROSPECTOR')]
final class RecognizeCallerController extends AbstractController
{
    public function __construct(private readonly CallerReader $callers)
    {
    }

    #[Route('/api/phone-numbers/{phoneNumber}', name: 'api_phone_numbers_recognize', methods: ['GET'])]
    #[OA\Get(summary: 'Recognize who calls: the organizations my organization prospects and its contacts having the phone number, given in E.164 or in the national format of France; needs the right to manage personal data')]
    #[OA\Response(response: 200, description: 'Who has the number, nobody when both lists are empty', content: new Model(type: CallerResource::class))]
    #[OA\Response(response: 401, description: 'Missing or invalid access token')]
    #[OA\Response(response: 403, description: 'Not a prospector, or without the right to manage personal data', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 422, description: 'No valid phone number', content: new Model(type: ProblemResource::class))]
    public function __invoke(string $phoneNumber, #[CurrentUser] User $user): JsonResponse
    {
        return $this->json($this->callers->recognize($user->memberId(), $phoneNumber));
    }
}
