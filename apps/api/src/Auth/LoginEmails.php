<?php

namespace App\Auth;

use Doctrine\ORM\EntityManagerInterface;
use Prospecter\Core\SharedKernel\Domain\Email;
use Prospecter\Core\SharedKernel\Domain\EmailAlreadyUsed;
use Prospecter\Core\SharedKernel\Domain\MemberId;

/**
 * The email a member signs in with: unique on the whole platform, since it tells which member, of which
 * account, is signing in.
 */
final readonly class LoginEmails
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    /**
     * @throws EmailAlreadyUsed when another member signs in with it
     */
    public function assign(MemberId $member, Email $email): void
    {
        $owner = $this->memberOf($email->value);

        if (null !== $owner && !$owner->equals($member)) {
            throw new EmailAlreadyUsed();
        }

        $credentials = $this->entityManager->find(Credentials::class, $member->value);

        if ($credentials) {
            $credentials->changeEmail($email->value);
        } else {
            $this->entityManager->persist(new Credentials($member->value, $email->value));
        }

        $this->entityManager->flush();
    }

    public function memberOf(string $email): ?MemberId
    {
        $credentials = $this->entityManager->getRepository(Credentials::class)->findOneBy(['email' => $email]);

        return null === $credentials ? null : new MemberId($credentials->memberId());
    }

    public function of(MemberId $member): string
    {
        return $this->entityManager->find(Credentials::class, $member->value)?->email() ?? throw new \LogicException(sprintf('Member %s has no login email', $member));
    }
}
