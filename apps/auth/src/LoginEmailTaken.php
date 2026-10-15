<?php

namespace App;

final class LoginEmailTaken extends \DomainException
{
    public function __construct()
    {
        parent::__construct('Email already used');
    }
}
