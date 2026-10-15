<?php

declare(strict_types=1);

namespace Prospecter\Core\Entity;

class User extends Person
{
    /** @var list<string> */
    private array $roles = [];
    private string $userName = '';
    private ?string $firstName = null;
    private ?string $lastName = null;
    private ?string $pictureUrl = null;
    private ?string $pictureExtension = null;
    private ?int $pictureSize = null;
    private ?Organization $organization = null;

    /**
     * @return list<string>
     */
    public function getRoles(): array
    {
        return $this->roles;
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): void
    {
        $this->roles = $roles;
    }

    public function addRole(string $role): void
    {
        $this->roles[] = $role;
    }

    public function getUserName(): string
    {
        return $this->userName;
    }

    public function setUserName(string $userName): void
    {
        $this->userName = $userName;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(?string $firstName): void
    {
        $this->firstName = $firstName;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(?string $lastName): void
    {
        $this->lastName = $lastName;
    }

    public function getFullName(): string
    {
        return trim(sprintf('%s %s', $this->firstName, $this->lastName));
    }

    public function getPicture(): ?File
    {
        return null === $this->pictureUrl ? null : new File($this->pictureUrl, (string) $this->pictureExtension, (int) $this->pictureSize);
    }

    public function setPicture(?File $picture): void
    {
        $this->pictureUrl = $picture?->url;
        $this->pictureExtension = $picture?->extension;
        $this->pictureSize = $picture?->size;
    }

    public function getOrganization(): Organization
    {
        return $this->organization ?? throw new \LogicException(sprintf('User "%s" has no organization', $this->userName));
    }

    public function setOrganization(Organization $organization): void
    {
        $this->organization = $organization;
    }
}
