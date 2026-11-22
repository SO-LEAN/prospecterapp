<?php

declare(strict_types=1);

namespace App\SignIn\Controller;

use App\SignIn\OAuth\BackToTheAi;
use App\SignIn\OAuth\ClientMetadataDocuments;
use App\SignIn\OAuth\CognitoSignIn;
use App\SignIn\OAuth\Pkce;
use App\SignIn\OAuth\ProtectedResource;
use App\SignIn\OAuth\Seal;
use App\SignIn\OAuth\SealPurpose;
use App\SignIn\OAuth\UnknownClient;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Public, outside the API and its contract: an AI asks to sign its member in (RFC 6749, ADR 0157). Only redirects:
 * once the AI is known by its metadata document to be sent back where it asks, the browser of the member goes to the
 * sign-in page of Cognito with the request of the AI sealed as its state, or back to the AI with what it got wrong.
 */
final class AuthorizeController extends AbstractController
{
    public function __construct(
        private readonly ClientMetadataDocuments $clients,
        private readonly CognitoSignIn $cognito,
        private readonly Seal $seal,
        private readonly ProtectedResource $mcp,
    ) {
    }

    #[Route('/oauth/authorize', name: 'oauth_authorize', methods: ['GET'])]
    public function __invoke(Request $request): RedirectResponse
    {
        $query = $request->query;
        $this->checkTheRedirectOf($query->getString('client_id'), $query->getString('redirect_uri'));

        $refusal = $this->refusalOf($query);

        return null === $refusal ? $this->toTheSignInPage($query) : $this->backToTheAi($query, ...$refusal);
    }

    /**
     * What the AI got wrong in its request, as RFC 6749 names it, or nothing.
     *
     * @param InputBag<string> $query
     *
     * @return array{string, string}|null
     */
    private function refusalOf(InputBag $query): ?array
    {
        return match (true) {
            'code' !== $query->getString('response_type') => ['unsupported_response_type', 'Only a code is given.'],
            Pkce::METHOD !== $query->getString('code_challenge_method'), '' === $query->getString('code_challenge') => ['invalid_request', 'A code challenge S256 is required.'],
            $query->has('resource') && $this->mcp->resource() !== $query->getString('resource') => ['invalid_target', 'The resource is the MCP server of Prospecter.'],
            default => null,
        };
    }

    private function checkTheRedirectOf(string $clientId, string $redirectUri): void
    {
        if (!$this->clients->of($clientId)->declares($redirectUri)) {
            throw UnknownClient::named($clientId, sprintf('it does not declare %s', $redirectUri));
        }
    }

    /**
     * @param InputBag<string> $query
     */
    private function backToTheAi(InputBag $query, string $error, string $description): RedirectResponse
    {
        return $this->redirect(BackToTheAi::at($query->getString('redirect_uri'), [
            'error' => $error,
            'error_description' => $description,
            'state' => $query->getString('state'),
        ]));
    }

    /**
     * @param InputBag<string> $query
     */
    private function toTheSignInPage(InputBag $query): RedirectResponse
    {
        $verifier = Pkce::newVerifier();
        $state = $this->seal->close([
            'client_id' => $query->getString('client_id'),
            'redirect_uri' => $query->getString('redirect_uri'),
            'code_challenge' => $query->getString('code_challenge'),
            'state' => $query->getString('state'),
            'verifier' => $verifier,
        ], SealPurpose::SignIn);

        return $this->redirect($this->cognito->pageFor($state, Pkce::challengeOf($verifier)));
    }
}
