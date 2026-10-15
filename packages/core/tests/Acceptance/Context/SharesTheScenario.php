<?php

declare(strict_types=1);

namespace Tests\Core\Acceptance\Context;

use Behat\Behat\Context\Environment\InitializedContextEnvironment;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Hook\BeforeScenario;
use Tests\Core\Acceptance\Application;

trait SharesTheScenario
{
    private ScenarioContext $scenario;
    private Application $application;

    #[BeforeScenario]
    public function gatherScenario(BeforeScenarioScope $scope): void
    {
        $environment = $scope->getEnvironment();

        if (!$environment instanceof InitializedContextEnvironment) {
            throw new \LogicException('The contexts of the scenario are not initialized');
        }

        $this->scenario = $environment->getContext(ScenarioContext::class);
        $this->application = $this->scenario->application;
    }
}
