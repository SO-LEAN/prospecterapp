<?php

declare(strict_types=1);

namespace Prospecter\Core\UseCase\UpdateMyAccountInformation;

use Prospecter\Core\Entity\Organization;
use Prospecter\Core\Entity\User;
use Prospecter\Core\Exception\Gateway;
use Prospecter\Core\Exception\UseCase\UniqueConstraintViolationException;
use Prospecter\Core\Gateway\Entity\OrganizationGateway;
use Prospecter\Core\Gateway\Entity\Transaction;
use Prospecter\Core\Gateway\Entity\UserGateway;
use Prospecter\Core\Gateway\Storage;
use Prospecter\Core\Gateway\UserNotifier;
use Prospecter\Core\Service\FileStore;
use Prospecter\Core\UseCase\AbstractUseCase;
use Prospecter\Core\UseCase\UseCaseConsumer;

final class UpdateMyAccountInformationImpl extends AbstractUseCase implements UpdateMyAccountInformation
{
    private readonly FileStore $files;

    public function __construct(
        private readonly OrganizationGateway $organizations,
        private readonly UserGateway $users,
        private readonly Transaction $transaction,
        Storage $storage,
        private readonly UserNotifier $userNotifier,
    ) {
        $this->files = new FileStore($storage);
    }

    public function canBeExecutedBy(): array
    {
        return ['ROLE_USER'];
    }

    public function execute(UpdateMyAccountInformationRequest $request, UpdateMyAccountInformationPresenter $presenter, UseCaseConsumer $consumer): mixed
    {
        $this->transaction->begin();
        $user = $this->users->get($consumer->getUserId());
        $organization = $this->organizations->get($consumer->getOrganizationId());

        $this->replaceProfile($user, $request);
        $this->replaceOrganizationDetails($organization, $request);
        $this->saveTogether($user, $organization);
        $this->userNotifier->addSuccess('User account information updated !');

        return $presenter->present($this->buildResponse($user, $organization));
    }

    private function replaceProfile(User $user, UpdateMyAccountInformationRequest $request): void
    {
        $user->setPhoneNumber($request->phoneNumber);
        $user->setEmail($request->email);
        $user->setLanguage($request->language);
        $user->setUserName($request->userName);
        $user->setFirstName($request->firstName);
        $user->setLastName($request->lastName);

        if ($request->picture) {
            $user->setPicture($this->files->store($request->picture));
        }
    }

    private function replaceOrganizationDetails(Organization $organization, UpdateMyAccountInformationRequest $request): void
    {
        $organization->setLanguage($request->language);
        $organization->setCorporateName($request->organizationCorporateName);
        $organization->setForm($request->organizationForm);

        if ($request->organizationLogo) {
            $organization->setLogo($this->files->store($request->organizationLogo));
        }
    }

    private function saveTogether(User $user, Organization $organization): void
    {
        try {
            $this->saveUser($user);
            $this->organizations->update((int) $organization->getId(), $organization);
            $this->transaction->commit();
        } catch (\Throwable $e) {
            $this->transaction->rollback();

            throw $e;
        }
    }

    private function saveUser(User $user): void
    {
        try {
            $this->users->update((int) $user->getId(), $user);
        } catch (Gateway\UniqueConstraintViolationException $e) {
            throw new UniqueConstraintViolationException('Email already used', ['email' => sprintf('Email "%s" already used', $user->getEmail())], $e);
        }
    }

    private function buildResponse(User $user, Organization $organization): UpdateMyAccountInformationResponse
    {
        return new UpdateMyAccountInformationResponse(
            userName: $user->getUserName(),
            firstName: $user->getFirstName(),
            lastName: $user->getLastName(),
            picture: $user->getPicture(),
            phoneNumber: $user->getPhoneNumber(),
            email: $user->getEmail(),
            language: $user->getLanguage(),
            organizationCorporateName: $organization->getCorporateName(),
            organizationForm: $organization->getForm(),
            organizationLogo: $organization->getLogo(),
        );
    }
}
