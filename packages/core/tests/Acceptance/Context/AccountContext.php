<?php

declare(strict_types=1);

namespace Tests\Core\Acceptance\Context;

use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\TableNode;
use Behat\Step\Given;
use Behat\Step\Then;
use Behat\Step\When;
use Prospecter\Core\Application\Account\AccountResponse;
use Prospecter\Core\Application\Account\ChangeMyContactDetailsRequest;
use Prospecter\Core\Application\Account\ChangeMyNameRequest;
use Prospecter\Core\Application\Account\ChangeMyOrganizationLogoRequest;
use Prospecter\Core\Application\Account\ChangeMyPictureRequest;
use Prospecter\Core\Application\Account\ChangeMyUserNameRequest;
use Prospecter\Core\Application\Account\ReviseMyOrganizationIdentityRequest;
use Prospecter\Core\Domain\Organization\LegalIdentity;
use Prospecter\Core\Domain\Organization\Organization;
use Prospecter\Core\Domain\Shared\ContactDetails;
use Prospecter\Core\Domain\Shared\Email;
use Tests\Core\Acceptance\Expect;
use Tests\Core\Acceptance\Vocabulary;

final class AccountContext implements Context
{
    use SharesTheScenario;

    private ?AccountResponse $account = null;

    #[Given('mon profil est :')]
    public function myProfileIs(TableNode $table): void
    {
        $values = Vocabulary::values($table->getRowsHash());
        $me = $this->scenario->me();
        $me->rename(Vocabulary::personName($values));
        $me->changeContactDetails(Vocabulary::contactDetails($values));
        $this->application->users->save($me);
    }

    #[Given('l\'organisation :name a l\'adresse e-mail :email')]
    public function anOrganizationHasTheEmail(string $name, string $email): void
    {
        $organization = Organization::usingProspecter($this->application->organizations->nextIdentity(), new LegalIdentity($name));
        $organization->changeContactDetails(new ContactDetails(new Email($email)));
        $this->scenario->save($name, $organization);
    }

    #[When('je consulte mon compte')]
    public function iConsultMyAccount(): void
    {
        $this->account = $this->application->consultMyAccount();
    }

    #[When('je change mon nom :')]
    public function iChangeMyName(TableNode $table): void
    {
        $this->application->changeMyName(new ChangeMyNameRequest(Vocabulary::personName(Vocabulary::values($table->getRowsHash()))));
    }

    #[When('je change mon identifiant en :userName')]
    public function iChangeMyUserName(string $userName): void
    {
        $this->application->changeMyUserName(new ChangeMyUserNameRequest($userName));
    }

    #[When('je change mes coordonnées :')]
    public function iChangeMyContactDetails(TableNode $table): void
    {
        $this->application->changeMyContactDetails(new ChangeMyContactDetailsRequest(Vocabulary::contactDetails(Vocabulary::values($table->getRowsHash()))));
    }

    #[When('je change ma photo avec :fileName')]
    public function iChangeMyPicture(string $fileName): void
    {
        $this->application->changeMyPicture(new ChangeMyPictureRequest($this->scenario->upload($fileName)));
    }

    #[When('je révise l\'identité de mon organisation :')]
    public function iReviseMyOrganizationIdentity(TableNode $table): void
    {
        $this->application->reviseMyOrganizationIdentity(new ReviseMyOrganizationIdentityRequest(Vocabulary::legalIdentity(Vocabulary::values($table->getRowsHash()))));
    }

    #[When('je change le logo de mon organisation avec :fileName')]
    public function iChangeMyOrganizationLogo(string $fileName): void
    {
        $this->application->changeMyOrganizationLogo(new ChangeMyOrganizationLogoRequest($this->scenario->upload($fileName)));
    }

    #[Then('je vois mon compte :')]
    public function iSeeMyAccount(TableNode $table): void
    {
        Expect::that(null !== $this->account, sprintf('My account was expected, got: %s', $this->application->refusal()?->getMessage()));
        $account = $this->account;

        Expect::describes($table->getRowsHash(), [
            'identifiant' => $account->userName,
            'prénom' => (string) $account->name->firstName,
            'nom' => (string) $account->name->lastName,
            'e-mail' => (string) $account->contactDetails->email,
            'raison sociale de mon organisation' => (string) $account->organizationIdentity->corporateName,
            'forme juridique de mon organisation' => (string) $account->organizationIdentity->form,
        ]);
    }

    #[Then('mon compte est enregistré avec :')]
    public function myAccountIsStoredWith(TableNode $table): void
    {
        $me = $this->scenario->me();

        Expect::describes($table->getRowsHash(), Vocabulary::describeAccount($me, $this->application->organizations->get($me->organizationId())));
    }
}
