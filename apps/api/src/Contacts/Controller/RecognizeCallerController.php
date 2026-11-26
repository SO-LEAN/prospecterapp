<?php

declare(strict_types=1);

namespace App\Contacts\Controller;

use App\Contacts\CallerComposition;
use App\Contacts\Resource\CallerResource;
use App\Security\User;
use App\Shared\JsonResponses;
use App\Shared\Resource\ProblemResource;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Recognizes who calls (ADR 0072). `CallerComposition` reads the answer.
 */
#[OA\Tag('Contacts')]
#[IsGranted('ROLE_PROSPECTOR')]
final readonly class RecognizeCallerController
{
    public function __construct(private CallerComposition $callers, private JsonResponses $jsonResponses)
    {
    }

    #[Route('/api/phone-numbers/{phoneNumber}', name: 'api_phone_numbers_recognize', methods: ['GET'])]
    #[OA\Get(summary: 'Recognize who calls: the organizations my organization prospects and the contacts that have the phone number. The number is in E.164 or in the French national format. This needs the right to manage personal data')]
    #[OA\Response(response: 200, description: 'Who has the number. Both lists are empty when nobody has it', content: new Model(type: CallerResource::class))]
    #[OA\Response(response: 401, description: 'Missing or invalid access token')]
    #[OA\Response(response: 403, description: 'Not a prospector, or without the right to manage personal data', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 422, description: 'No valid phone number', content: new Model(type: ProblemResource::class))]
    public function __invoke(string $phoneNumber, #[CurrentUser] User $user): JsonResponse
    {
        return $this->jsonResponses->of($this->callers->recognize($user->memberId(), $phoneNumber));
    }
}
