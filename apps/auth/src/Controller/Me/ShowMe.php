<?php

namespace App\Controller\Me;

use App\Resource\LinkResource;
use App\Resource\MeLinksResource;
use App\Resource\MeResource;
use App\Security\SignedInMember;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[OA\Tag('Sign-in')]
final class ShowMe extends AbstractController
{
    #[Route('/auth/me', name: 'auth_me', methods: ['GET'])]
    #[OA\Get(summary: 'The email I sign in with')]
    #[OA\Response(response: 200, description: 'What I sign in with', content: new Model(type: MeResource::class))]
    #[OA\Response(response: 401, description: 'Missing or invalid access token')]
    public function __invoke(#[CurrentUser] SignedInMember $member): JsonResponse
    {
        return $this->json(self::resourceOf($member->loginEmail(), fn (string $route): string => $this->generateUrl($route)));
    }

    /**
     * @param \Closure(string): string $url
     */
    public static function resourceOf(string $loginEmail, \Closure $url): MeResource
    {
        return new MeResource($loginEmail, new MeLinksResource(
            new LinkResource($url('auth_me'), 'GET'),
            new LinkResource($url('auth_me_change_login_email'), 'PATCH'),
            new LinkResource($url('auth_me_change_password'), 'PATCH'),
        ));
    }
}
