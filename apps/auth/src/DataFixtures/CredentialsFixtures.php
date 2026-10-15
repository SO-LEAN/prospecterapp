<?php

namespace App\DataFixtures;

use App\LoginEmails;
use App\Passwords;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

/**
 * The prospector of the API fixtures signs in with prospector@solean-it.io / password. Its member id is the
 * one the API fixtures give it: the two apps share it, nothing else.
 */
class CredentialsFixtures extends Fixture
{
    public const string PROSPECTOR = '01900000-0000-7000-8000-000000000001';
    public const string ASSISTANT = '01900000-0000-7000-8000-000000000002';

    public function __construct(private readonly LoginEmails $loginEmails, private readonly Passwords $passwords)
    {
    }

    public function load(ObjectManager $manager): void
    {
        $this->loginEmails->assign(self::PROSPECTOR, 'prospector@solean-it.io');
        $this->passwords->set(self::PROSPECTOR, 'password');
        $this->loginEmails->assign(self::ASSISTANT, 'assistant@solean-it.io');
        $this->passwords->set(self::ASSISTANT, 'password');
    }
}
