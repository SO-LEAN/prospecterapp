<?php

declare(strict_types=1);

namespace App\Connectors\Google;

use App\Connectors\GmailNotificationEndpoint;
use App\Security\Expires;
use App\Shared\Typed;
use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use Lcobucci\JWT\Token\Parser;
use Lcobucci\JWT\UnencryptedToken;
use Lcobucci\JWT\Validation\Constraint;
use Lcobucci\JWT\Validation\Constraint\HasClaimWithValue;
use Lcobucci\JWT\Validation\Constraint\IssuedBy;
use Lcobucci\JWT\Validation\Constraint\LooseValidAt;
use Lcobucci\JWT\Validation\Constraint\PermittedFor;
use Lcobucci\JWT\Validation\Constraint\SignedWith;
use Lcobucci\JWT\Validation\Validator;
use Prospecter\Core\Connectors\Application\Client\ConsultMailboxPusher\ConsultMailboxPusher;
use Prospecter\Core\Connectors\Application\Client\ConsultMailboxPusher\KeptMailboxPusher;
use Prospecter\Core\SharedKernel\Application\Handler\QueryHandler;
use Psr\Clock\ClockInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

/**
 * Trusts a Gmail notification that Pub/Sub pushes to an account's endpoint (ADR 0154). Its token must meet these
 * conditions:
 * - Google signed it with one of its published keys;
 * - Google issued it for that endpoint;
 * - it names the service account that the account gave with its topic, and that address is verified;
 * - it has not expired.
 *
 * The check calls Google only for its keys. Any other notification is refused, and so is an account without a topic.
 * Pub/Sub then retries the notification until its retention ends.
 */
final class GmailPushAuthenticator extends AbstractAuthenticator
{
    private const array ISSUERS = ['https://accounts.google.com', 'accounts.google.com'];
    private const string GOOGLE = 'google';
    private const string BEARER = 'Bearer ';

    public function __construct(
        private readonly GoogleSigningKeys $keys,
        private readonly QueryHandler $consultMailboxPusher,
        private readonly GmailNotificationEndpoint $endpoint,
        private readonly ClockInterface $clock,
    ) {
    }

    public function supports(Request $request): bool
    {
        return true;
    }

    public function authenticate(Request $request): SelfValidatingPassport
    {
        $account = $request->attributes->getString('account');
        $token = $this->tokenIn($request);

        if (!new Validator()->validate($token, ...$this->constraintsFor($token, $account))) {
            throw new BadCredentialsException('This notification was not pushed for the account.');
        }

        return new SelfValidatingPassport(new UserBadge($account, static fn (string $account): GmailPusher => new GmailPusher($account)));
    }

    private function tokenIn(Request $request): UnencryptedToken
    {
        $authorization = (string) $request->headers->get('Authorization');

        try {
            $token = new Parser(new JoseEncoder())->parse(Typed::filledString(substr($authorization, strlen(self::BEARER))));
        } catch (\Throwable) {
            throw new BadCredentialsException('This notification has no token.');
        }

        return $token instanceof UnencryptedToken && str_starts_with($authorization, self::BEARER) ? $token : throw new BadCredentialsException('This notification has no token.');
    }

    /**
     * @return list<Constraint>
     */
    private function constraintsFor(UnencryptedToken $token, string $account): array
    {
        $kid = $token->headers()->get('kid');
        $key = (is_string($kid) ? $this->keys->named($kid) : null) ?? throw new BadCredentialsException('This notification was not signed by Google.');

        return [
            new SignedWith(new Sha256(), InMemory::plainText(Typed::filledString($key))),
            new IssuedBy(...self::ISSUERS),
            new PermittedFor(Typed::filledString($this->endpoint->of($account))),
            new HasClaimWithValue('email', $this->pushAccountOf($account)),
            new HasClaimWithValue('email_verified', true),
            new Expires(),
            new LooseValidAt($this->clock),
        ];
    }

    private function pushAccountOf(string $account): string
    {
        $pusher = new KeptMailboxPusher();
        $this->consultMailboxPusher->__invoke(new ConsultMailboxPusher($account, self::GOOGLE), $pusher);

        return $pusher->pushAccount ?? throw new BadCredentialsException('This account has no Gmail topic.');
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): JsonResponse
    {
        return new JsonResponse(['title' => 'Unauthorized', 'status' => Response::HTTP_UNAUTHORIZED], Response::HTTP_UNAUTHORIZED);
    }
}
