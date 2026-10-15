<?php

namespace App;

use Doctrine\ORM\EntityManagerInterface;

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
     * @throws LoginEmailTaken when another member signs in with it
     */
    public function assign(string $memberId, string $email): void
    {
        $owner = $this->credentialsOf($email);

        if (null !== $owner && $owner->memberId() !== $memberId) {
            throw new LoginEmailTaken();
        }

        $credentials = $this->entityManager->find(Credentials::class, $memberId);

        if ($credentials) {
            $credentials->changeEmail($email);
        } else {
            $this->entityManager->persist(new Credentials($memberId, $email));
        }

        $this->entityManager->flush();
    }

    public function credentialsOf(string $email): ?Credentials
    {
        return $this->entityManager->getRepository(Credentials::class)->findOneBy(['email' => $email]);
    }

    public function of(string $memberId): ?Credentials
    {
        return $this->entityManager->find(Credentials::class, $memberId);
    }
}
