<?php

namespace App\SignIn\Command;

use App\Demo\DemoCredentials;
use App\Demo\DemoData;
use App\SignIn\UserPool;
use Aws\CognitoIdentityProvider\CognitoIdentityProviderClient;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * The proof of the spike #202 only: an access token of the prospector of the demonstration, for curl.
 */
#[AsCommand('app:spike:token')]
final readonly class SpikeTokenCommand
{
    public function __construct(private UserPool $pool, private CognitoIdentityProviderClient $cognito)
    {
    }

    public function __invoke(OutputInterface $output): int
    {
        $result = $this->cognito->adminInitiateAuth([
            'UserPoolId' => $this->pool->id(),
            'ClientId' => $this->pool->clientId(),
            'AuthFlow' => 'ADMIN_USER_PASSWORD_AUTH',
            'AuthParameters' => ['USERNAME' => DemoData::PROSPECTOR, 'PASSWORD' => DemoCredentials::PASSWORD],
        ])['AuthenticationResult'];
        $output->writeln($result['AccessToken']);

        return Command::SUCCESS;
    }
}
