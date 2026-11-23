<?php

declare(strict_types=1);

namespace App\SignIn\Controller;

use App\SignIn\OAuth\BackToTheAi;
use App\SignIn\OAuth\BrokenSeal;
use App\SignIn\OAuth\Seal;
use App\SignIn\OAuth\SealPurpose;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Public, outside the API and its contract. Cognito sends the member back here once they are signed in (ADR 0157).
 * It only redirects to the AI, and gives back the AI's state. When the member signed in, the code it gives is the
 * AI's request and Cognito's code sealed together. Otherwise, it gives Cognito's error. A state that the facade did
 * not seal leads nowhere.
 */
final readonly class ComeBackFromCognitoController
{
    public function __construct(private Seal $seal)
    {
    }

    #[Route('/oauth/callback', name: 'oauth_callback', methods: ['GET'])]
    public function __invoke(Request $request): RedirectResponse
    {
        $authorization = $this->authorizationSealedIn($request->query->getString('state'));

        return new RedirectResponse(BackToTheAi::at($authorization['redirect_uri'], [...$this->answerOfCognito($request->query, $authorization), 'state' => $authorization['state']]));
    }

    /**
     * @return array<string, string>
     */
    private function authorizationSealedIn(string $state): array
    {
        try {
            return $this->seal->open($state, SealPurpose::SignIn);
        } catch (BrokenSeal $broken) {
            throw new BadRequestHttpException('The facade did not send the member to sign in.', $broken);
        }
    }

    /**
     * @param InputBag<string>      $query
     * @param array<string, string> $authorization
     *
     * @return array<string, string>
     */
    private function answerOfCognito(InputBag $query, array $authorization): array
    {
        if ($query->has('error')) {
            return ['error' => $query->getString('error')];
        }

        return ['code' => $this->seal->close([...$authorization, 'cognito_code' => $query->getString('code')], SealPurpose::AuthorizationCode)];
    }
}
