<?php

namespace App\Controller;

use App\Input\SignInInput;
use App\Resource\ProblemResource;
use App\Resource\SignInResource;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Component\Routing\Attribute\Route;

#[OA\Tag('Sign-in')]
final class SignIn
{
    /**
     * @codeCoverageIgnore
     */
    #[Route('/login', name: 'sign_in', methods: ['POST'])]
    #[OA\Post(summary: 'Sign in with my login email and password, from the SPA only (session cookie of the authorization server)', security: [])]
    #[OA\RequestBody(content: new Model(type: SignInInput::class))]
    #[OA\Response(response: 200, description: 'Signed in: open the interrupted authorization request again', content: new Model(type: SignInResource::class))]
    #[OA\Response(response: 401, description: 'Wrong login email or password', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 403, description: 'Not sent by the SPA', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 429, description: 'Too many failed attempts: wait a minute', content: new Model(type: ProblemResource::class))]
    public function __invoke(): never
    {
        throw new \LogicException('This method is intercepted by App\Security\SignInAuthenticator.');
    }
}
