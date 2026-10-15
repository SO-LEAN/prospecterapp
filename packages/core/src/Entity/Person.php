<?php

declare(strict_types=1);

namespace Prospecter\Core\Entity;

abstract class Person extends Base
{
    private ?string $email = null;
    private ?string $phoneNumber = null;
    private ?string $language = null;
    private ?string $observations = null;

    abstract public function getFullName(): string;

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): void
    {
        if (null !== $email && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException(sprintf('Email "%s" is not valid', $email));
        }

        $this->email = $email;
    }

    public function getPhoneNumber(): ?string
    {
        return $this->phoneNumber;
    }

    public function setPhoneNumber(?string $phoneNumber): void
    {
        $this->phoneNumber = $phoneNumber;
    }

    public function getLanguage(): ?string
    {
        return $this->language;
    }

    public function setLanguage(?string $language): void
    {
        $this->language = $language;
    }

    public function getObservations(): ?string
    {
        return $this->observations;
    }

    public function setObservations(?string $observations): void
    {
        $this->observations = $observations;
    }
}
