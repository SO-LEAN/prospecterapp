<?php

declare(strict_types=1);

namespace App\SignIn\Controller;

use App\SignIn\OAuth\BackToTheAi;
use App\SignIn\OAuth\BrokenSeal;
use App\SignIn\OAuth\Seal;
use App\SignIn\OAuth\SealPurpose;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Public, outside the API and its contract: Cognito sends the member back once signed in (ADR 0157). Only redirects:
 * to the AI, its state given back and, as its code, its request and the code of Cognito sealed together; or, the
 * member not signed in, with the error of Cognito. A state the facade did not seal leads nowhere.
 */
final class ComeBackFromCognitoController extends AbstractController
{
    public function __construct(private readonly Seal $seal)
    {
    }

    #[Route('/oauth/callback', name: 'oauth_callback', methods: ['GET'])]
    public function __invoke(Request $request): RedirectResponse
    {
        $authorization = $this->authorizationSealedIn($request->query->getString('state'));

        return $this->redirect(BackToTheAi::at($authorization['redirect_uri'], [...$this->answerOfCognito($request->query, $authorization), 'state' => $authorization['state']]));
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
