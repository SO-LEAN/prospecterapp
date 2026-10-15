<?php

namespace App;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
use Symfony\Component\PasswordHasher\PasswordHasherInterface;

/**
 * The hashing algorithm is the one configured for Credentials in security.yaml.
 */
final readonly class Passwords
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private PasswordHasherFactoryInterface $hashers,
    ) {
    }

    public function set(string $memberId, string $password): void
    {
        $credentials = $this->entityManager->find(Credentials::class, $memberId) ?? throw new \LogicException(sprintf('Member %s has no login email: assign one first', $memberId));
        $credentials->changePasswordHash($this->hasher()->hash($password));
        $this->entityManager->flush();
    }

    public function verify(string $memberId, string $password): bool
    {
        $credentials = $this->entityManager->find(Credentials::class, $memberId);
        $hash = $credentials?->passwordHash();

        if (null === $credentials || null === $hash || !$this->hasher()->verify($hash, $password)) {
            return false;
        }

        $this->upgradeHashIfOutdated($credentials, $password);

        return true;
    }

    private function upgradeHashIfOutdated(Credentials $credentials, string $password): void
    {
        if ($this->hasher()->needsRehash((string) $credentials->passwordHash())) {
            $this->set($credentials->memberId(), $password);
        }
    }

    private function hasher(): PasswordHasherInterface
    {
        return $this->hashers->getPasswordHasher(Credentials::class);
    }
}
