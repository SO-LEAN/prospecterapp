<?php

declare(strict_types=1);

namespace App\SignIn\Controller;

use App\SignIn\OAuth\BrokenSeal;
use App\SignIn\OAuth\CognitoTokens;
use App\SignIn\OAuth\GrantRefused;
use App\SignIn\OAuth\Pkce;
use App\SignIn\OAuth\Seal;
use App\SignIn\OAuth\SealPurpose;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Public, outside the API and its contract: an AI exchanges its code of the facade for the tokens of Cognito
 * (RFC 6749, ADR 0157), once its verifier answers the challenge it gave.
 */
final class IssueTokensController extends AbstractController
{
    public function __construct(
        private readonly Seal $seal,
        private readonly CognitoTokens $cognito,
    ) {
    }

    #[Route('/oauth/token', name: 'oauth_token', methods: ['POST'])]
    public function __invoke(Request $request): JsonResponse
    {
        $form = $request->request;
        $tokens = match ($form->getString('grant_type')) {
            'authorization_code' => $this->tokensForTheCodeIn($form),
            'refresh_token' => $this->cognito->refreshed($form->getString('refresh_token')),
            default => throw GrantRefused::ofTheType($form->getString('grant_type')),
        };

        return new JsonResponse($tokens, headers: ['Cache-Control' => 'no-store']);
    }

    /**
     * @param InputBag<string> $form
     *
     * @return array<string, mixed>
     */
    private function tokensForTheCodeIn(InputBag $form): array
    {
        $authorization = $this->authorizationOf($form);

        return $this->cognito->forCode($authorization['cognito_code'], $authorization['verifier']);
    }

    /**
     * @param InputBag<string> $form
     *
     * @return array<string, string>
     */
    private function authorizationOf(InputBag $form): array
    {
        $authorization = $this->authorizationSealedIn($form->getString('code'));

        if (!self::isAskedBy($form, $authorization)) {
            throw GrantRefused::because('The code was given to another request.');
        }

        return $authorization;
    }

    /**
     * @return array<string, string>
     */
    private function authorizationSealedIn(string $code): array
    {
        try {
            return $this->seal->open($code, SealPurpose::AuthorizationCode);
        } catch (BrokenSeal $broken) {
            throw GrantRefused::because('The facade did not give this code, or no longer accepts it.', $broken);
        }
    }

    /**
     * The client the code was given to, for the redirect it was given for, proving the challenge it gave.
     *
     * @param InputBag<string>      $form
     * @param array<string, string> $authorization
     */
    private static function isAskedBy(InputBag $form, array $authorization): bool
    {
        return $authorization['client_id'] === $form->getString('client_id')
            && $authorization['redirect_uri'] === $form->getString('redirect_uri')
            && Pkce::answers($form->getString('code_verifier'), $authorization['code_challenge']);
    }
}
