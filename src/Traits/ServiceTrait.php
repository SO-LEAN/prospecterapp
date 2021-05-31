<?php

namespace App\Traits;

use InvalidArgumentException;

trait ServiceTrait
{
    /**
     * @var array
     */
    private $services = [];

    private function add(string $name, $service)
    {
        $this->services[$name] = $service;
    }

    private function has(string $name)
    {
        return isset($this->services[$name]);
    }

    private function get(string $name)
    {
        if (!$this->has($name)) {
            throw new InvalidArgumentException(sprintf('Request unknown service : "%s"', $name));
        }

        return $this->services[$name];
    }
}
