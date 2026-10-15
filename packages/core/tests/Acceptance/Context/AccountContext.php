<?php

declare(strict_types=1);

namespace Tests\Core\Acceptance\Context;

use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\TableNode;
use Behat\Step\Given;
use Behat\Step\Then;
use Behat\Step\When;
use Prospecter\Core\Entity\Organization;
use Prospecter\Core\UseCase\GetMyAccountInformation\GetMyAccountInformationResponse;
use Prospecter\Core\UseCase\UpdateMyAccountInformation\UpdateMyAccountInformationRequest;
use Tests\Core\Acceptance\Expect;
use Tests\Core\Acceptance\Vocabulary;

final class AccountContext implements Context
{
    use SharesTheScenario;

    private ?GetMyAccountInformationResponse $account = null;

    #[Given('mon profil est :')]
    public function myProfileIs(TableNode $table): void
    {
        $profile = $table->getRowsHash();
        $me = $this->scenario->me();

        $me->setFirstName($profile['prénom'] ?? null);
        $me->setLastName($profile['nom'] ?? null);
        $me->setEmail($profile['e-mail'] ?? null);
        $me->setPhoneNumber($profile['téléphone'] ?? null);
        $me->setLanguage($profile['langue'] ?? null);
        $this->application->users->update((int) $me->getId(), $me);
    }

    #[Given('l\'organisation :name a l\'adresse e-mail :email')]
    public function anOrganizationHasTheEmail(string $name, string $email): void
    {
        $organization = new Organization();
        $organization->setCorporateName($name);
        $organization->setEmail($email);
        $this->application->organizations->create($organization);
    }

    #[When('je consulte mon compte')]
    public function iConsultMyAccount(): void
    {
        $this->account = $this->application->getMyAccountInformation();
    }

    #[When('je mets à jour mon compte avec :')]
    public function iUpdateMyAccount(TableNode $table): void
    {
        $parameters = Vocabulary::parameters($table->getRowsHash(), Vocabulary::ACCOUNT);
        $parameters['picture'] = $this->scenario->upload($parameters['picture']);
        $parameters['organizationLogo'] = $this->scenario->upload($parameters['organizationLogo']);
        $parameters['userName'] ??= $this->scenario->me()->getUserName();

        $this->application->updateMyAccountInformation(new UpdateMyAccountInformationRequest(...$parameters));
    }

    #[Then('je vois mon compte :')]
    public function iSeeMyAccount(TableNode $table): void
    {
        Expect::that(null !== $this->account, sprintf('My account was expected, got: %s', $this->application->refusal()?->getMessage()));
        $expected = $table->getRowsHash();

        Expect::describes($expected, [
            'identifiant' => $this->account->userName,
            'prénom' => (string) $this->account->firstName,
            'nom' => (string) $this->account->lastName,
            'e-mail' => (string) $this->account->email,
            'raison sociale de mon organisation' => (string) $this->account->organizationCorporateName,
            'forme juridique de mon organisation' => (string) $this->account->organizationForm,
        ]);
    }

    #[Then('mon compte est enregistré avec :')]
    public function myAccountIsStoredWith(TableNode $table): void
    {
        $me = $this->scenario->me();
        $expected = $table->getRowsHash();

        Expect::describes(
            $expected,
            Vocabulary::describeAccount($me, $this->application->organizations->get($this->application->signedIn()->getOrganizationId())),
        );
    }
}
