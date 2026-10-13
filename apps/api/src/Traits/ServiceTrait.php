<?php

namespace App\Traits;

trait ServiceTrait
{
    /**
     * @var array<string, object>
     */
    private array $services = [];

    private function add(string $name, object $service): void
    {
        $this->services[$name] = $service;
    }

    private function has(string $name): bool
    {
        return isset($this->services[$name]);
    }

    private function get(string $name): object
    {
        if (!$this->has($name)) {
            throw new \InvalidArgumentException(sprintf('Request unknown service : "%s"', $name));
        }

        return $this->services[$name];
    }
}
