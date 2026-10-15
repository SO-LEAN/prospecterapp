<?php

namespace App\Auth;

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

    public function set(int $userId, string $password): void
    {
        $hash = $this->hasher()->hash($password);
        $credentials = $this->entityManager->find(Credentials::class, $userId);

        if ($credentials) {
            $credentials->changePasswordHash($hash);
        } else {
            $this->entityManager->persist(new Credentials($userId, $hash));
        }

        $this->entityManager->flush();
    }

    public function verify(int $userId, string $password): bool
    {
        $credentials = $this->entityManager->find(Credentials::class, $userId);

        if (!$credentials || !$this->hasher()->verify($credentials->passwordHash(), $password)) {
            return false;
        }

        $this->upgradeHashIfOutdated($credentials, $password);

        return true;
    }

    private function upgradeHashIfOutdated(Credentials $credentials, string $password): void
    {
        if ($this->hasher()->needsRehash($credentials->passwordHash())) {
            $this->set($credentials->userId(), $password);
        }
    }

    private function hasher(): PasswordHasherInterface
    {
        return $this->hashers->getPasswordHasher(Credentials::class);
    }
}
