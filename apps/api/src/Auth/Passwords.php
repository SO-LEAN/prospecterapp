<?php

namespace App\Auth;

use Doctrine\ORM\EntityManagerInterface;
use Prospecter\Core\SharedKernel\Domain\MemberId;
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

    public function set(MemberId $member, string $password): void
    {
        $credentials = $this->entityManager->find(Credentials::class, $member->value) ?? throw new \LogicException(sprintf('Member %s has no login email: assign one first', $member));
        $credentials->changePasswordHash($this->hasher()->hash($password));
        $this->entityManager->flush();
    }

    public function verify(MemberId $member, string $password): bool
    {
        $credentials = $this->entityManager->find(Credentials::class, $member->value);
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
            $this->set(new MemberId($credentials->memberId()), $password);
        }
    }

    private function hasher(): PasswordHasherInterface
    {
        return $this->hashers->getPasswordHasher(Credentials::class);
    }
}
