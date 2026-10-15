<?php

declare(strict_types=1);

namespace Tests\Core\Acceptance\Context;

use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\TableNode;
use Behat\Step\Given;
use Behat\Step\Then;
use Behat\Step\When;
use Prospecter\Core\Accounts\Application\AccountResponse;
use Prospecter\Core\Accounts\Application\ChangeMyOrganizationLogo\ChangeMyOrganizationLogo;
use Prospecter\Core\Accounts\Application\ChangeMyUserName\ChangeMyUserName;
use Prospecter\Core\Accounts\Application\ReviseMyOrganizationIdentity\ReviseMyOrganizationIdentity;
use Prospecter\Core\Accounts\Domain\Account;
use Prospecter\Core\PersonalData\Application\ChangePersonContactDetails\ChangePersonContactDetails;
use Prospecter\Core\PersonalData\Application\ChangePersonPicture\ChangePersonPicture;
use Prospecter\Core\PersonalData\Application\ConsultPerson\ConsultPerson;
use Prospecter\Core\PersonalData\Application\PersonResponse;
use Prospecter\Core\PersonalData\Application\RenamePerson\RenamePerson;
use Prospecter\Core\SharedKernel\Domain\ContactDetails;
use Prospecter\Core\SharedKernel\Domain\Email;
use Prospecter\Core\SharedKernel\Domain\LegalIdentity;
use Tests\Core\Acceptance\Expect;
use Tests\Core\Acceptance\Vocabulary;

final class AccountContext implements Context
{
    use SharesTheScenario;

    private ?AccountResponse $account = null;
    private ?PersonResponse $myPerson = null;

    #[Given('mon profil est :')]
    public function myProfileIs(TableNode $table): void
    {
        $values = Vocabulary::values($table->getRowsHash());
        $me = $this->scenario->myPerson();
        $me->rename(Vocabulary::personName($values), by: $this->scenario->meAsReader());
        $me->changeContactDetails(Vocabulary::contactDetails($values), by: $this->scenario->meAsReader());
        $this->application->persons->save($me);
    }

    #[Given('l\'organisation :name a l\'adresse e-mail :email')]
    public function anOrganizationHasTheEmail(string $name, string $email): void
    {
        $this->scenario->openAccount($name, Account::open($this->application->accounts->nextIdentity(), new LegalIdentity($name), new ContactDetails(new Email($email))));
    }

    #[When('je consulte mon compte')]
    public function iConsultMyAccount(): void
    {
        $this->account = $this->application->consultMyAccount();
        $this->myPerson = $this->application->consultPerson(new ConsultPerson($this->application->signedIn(), $this->scenario->me()->personId()));
    }

    #[When('je change mon nom :')]
    public function iChangeMyName(TableNode $table): void
    {
        $this->application->dispatch(new RenamePerson($this->application->signedIn(), $this->scenario->me()->personId(), Vocabulary::personName(Vocabulary::values($table->getRowsHash()))));
    }

    #[When('je change mon identifiant en :userName')]
    public function iChangeMyUserName(string $userName): void
    {
        $this->application->dispatch(new ChangeMyUserName($this->application->signedIn(), $userName));
    }

    #[When('je change mes coordonnées :')]
    public function iChangeMyContactDetails(TableNode $table): void
    {
        $this->application->dispatch(new ChangePersonContactDetails($this->application->signedIn(), $this->scenario->me()->personId(), Vocabulary::contactDetails(Vocabulary::values($table->getRowsHash()))));
    }

    #[When('je change ma photo avec :fileName')]
    public function iChangeMyPicture(string $fileName): void
    {
        $this->application->dispatch(new ChangePersonPicture($this->application->signedIn(), $this->scenario->me()->personId(), $this->scenario->upload($fileName)));
    }

    #[When('je révise l\'identité de mon organisation :')]
    public function iReviseMyOrganizationIdentity(TableNode $table): void
    {
        $this->application->dispatch(new ReviseMyOrganizationIdentity($this->application->signedIn(), Vocabulary::legalIdentity(Vocabulary::values($table->getRowsHash()))));
    }

    #[When('je change le logo de mon organisation avec :fileName')]
    public function iChangeMyOrganizationLogo(string $fileName): void
    {
        $this->application->dispatch(new ChangeMyOrganizationLogo($this->application->signedIn(), $this->scenario->upload($fileName)));
    }

    #[Then('je vois mon compte :')]
    public function iSeeMyAccount(TableNode $table): void
    {
        Expect::that(null !== $this->account && null !== $this->myPerson, sprintf('My account was expected, got: %s', $this->application->refusal()?->getMessage()));
        $account = $this->account;
        $me = $this->myPerson;

        Expect::describes($table->getRowsHash(), [
            'identifiant' => $account->userName,
            'prénom' => (string) $me->firstName,
            'nom' => (string) $me->lastName,
            'e-mail' => (string) $me->email,
            'raison sociale de mon organisation' => (string) $account->organizationIdentity->corporateName,
            'forme juridique de mon organisation' => (string) $account->organizationIdentity->form,
        ]);
    }

    #[Then('je peux sur mon compte :')]
    public function iMayOnMyAccount(TableNode $table): void
    {
        Expect::that(null !== $this->account, 'My account was expected');
        Expect::same($table->getColumn(0), Vocabulary::describeIntents($this->account->possibleIntents));
    }

    #[Then('je peux sur mon profil :')]
    public function iMayOnMyProfile(TableNode $table): void
    {
        Expect::that(null !== $this->myPerson, 'My profile was expected');
        Expect::same($table->getColumn(0), Vocabulary::describeIntents($this->myPerson->possibleIntents));
    }

    #[Then('je peux sur mon organisation :')]
    public function iMayOnMyOrganization(TableNode $table): void
    {
        Expect::that(null !== $this->account, 'My account was expected');
        Expect::same($table->getColumn(0), Vocabulary::describeIntents($this->account->accountIntents));
    }

    #[Then('mon compte est enregistré avec :')]
    public function myAccountIsStoredWith(TableNode $table): void
    {
        $me = $this->scenario->me();

        Expect::describes($table->getRowsHash(), Vocabulary::describeAccount($me, $this->application->accounts->get($me->accountId()), $this->scenario->myPerson()));
    }
}
