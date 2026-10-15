<?php

namespace App\Security;

use App\Passwords;
use App\Resource\ProblemResource;
use App\Resource\SignInResource;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Exception\JsonException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\Exception\TooManyLoginAttemptsAuthenticationException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\CustomCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;
use Symfony\Component\Security\Http\Util\TargetPathTrait;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * The SPA signs a member in with a JSON POST of their login email and password (ADR 0037): the authorization
 * server keeps them signed in with its session cookie, and answers the authorization request the sign-in
 * interrupted, which the SPA opens again to get its code. An anonymous authorization request is sent to the
 * sign-in screen of the SPA.
 */
final class SignInAuthenticator extends AbstractAuthenticator implements AuthenticationEntryPointInterface
{
    use TargetPathTrait;

    public const string SIGN_IN_SCREEN = '/auth/sign-in';
    private const string SIGN_IN_PATH = '/login';

    public function __construct(
        private readonly MemberProvider $members,
        private readonly Passwords $passwords,
        private readonly NormalizerInterface $normalizer,
        #[Autowire(env: 'FRONT_URL')]
        private readonly string $frontUrl,
    ) {
    }

    public function start(Request $request, ?AuthenticationException $authException = null): RedirectResponse
    {
        return new RedirectResponse($this->frontUrl.self::SIGN_IN_SCREEN);
    }

    public function supports(Request $request): bool
    {
        return self::SIGN_IN_PATH === $request->getPathInfo() && $request->isMethod('POST');
    }

    public function authenticate(Request $request): Passport
    {
        $this->ensureSentByTheApplication($request);
        [$loginEmail, $password] = $this->credentialsOf($request);

        return new Passport(
            new UserBadge($loginEmail, $this->members->byLoginEmail(...)),
            new CustomCredentials(fn (): bool => $this->checkPassword($loginEmail, $password), null),
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): JsonResponse
    {
        $resume = $this->getTargetPath($request->getSession(), $firewallName);
        $this->removeTargetPath($request->getSession(), $firewallName);

        return new JsonResponse($this->normalizer->normalize(new SignInResource($resume)));
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): JsonResponse
    {
        $problem = $this->problemOf($exception);

        return new JsonResponse($this->normalizer->normalize($problem), $problem->status, ['Content-Type' => 'application/problem+json']);
    }

    private function ensureSentByTheApplication(Request $request): void
    {
        if ($this->frontUrl !== $request->headers->get('Origin')) {
            throw new NotFromTheApplication();
        }
    }

    /**
     * @return array{string, string}
     */
    private function credentialsOf(Request $request): array
    {
        try {
            $body = $request->toArray();
        } catch (JsonException) {
            throw new BadCredentialsException('The credentials are a JSON object');
        }

        return [(string) ($body['email'] ?? ''), (string) ($body['password'] ?? '')];
    }

    private function checkPassword(string $loginEmail, string $password): bool
    {
        if (!$this->isPasswordOf($loginEmail, $password)) {
            throw new BadCredentialsException('Bad credentials');
        }

        return true;
    }

    private function isPasswordOf(string $loginEmail, string $password): bool
    {
        try {
            return $this->passwords->verify($this->members->byLoginEmail($loginEmail)->getUserIdentifier(), $password);
        } catch (UserNotFoundException) {
            return false;
        }
    }

    private function problemOf(AuthenticationException $exception): ProblemResource
    {
        return match (true) {
            $exception instanceof NotFromTheApplication => new ProblemResource('Forbidden', Response::HTTP_FORBIDDEN, $exception->getMessageKey()),
            $exception instanceof TooManyLoginAttemptsAuthenticationException => new ProblemResource('Too Many Requests', Response::HTTP_TOO_MANY_REQUESTS, 'Too many failed sign-in attempts: wait a minute.'),
            default => new ProblemResource('Unauthorized', Response::HTTP_UNAUTHORIZED, 'Wrong login email or password.'),
        };
    }
}
