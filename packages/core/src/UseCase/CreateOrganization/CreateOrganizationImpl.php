<?php

declare(strict_types=1);

namespace Prospecter\Core\UseCase\CreateOrganization;

use Prospecter\Core\Entity\Organization;
use Prospecter\Core\Exception\Entity\ValidationException;
use Prospecter\Core\Exception\Gateway;
use Prospecter\Core\Exception\UseCase\UniqueConstraintViolationException;
use Prospecter\Core\Exception\UseCase\UseCaseException;
use Prospecter\Core\Gateway\Entity\OrganizationGateway;
use Prospecter\Core\Gateway\GeoLocation;
use Prospecter\Core\Gateway\Storage;
use Prospecter\Core\Gateway\UserNotifier;
use Prospecter\Core\Service\FileStore;
use Prospecter\Core\Service\GeoLocalizer;
use Prospecter\Core\Service\ProspectedOrganizations;
use Prospecter\Core\UseCase\AbstractUseCase;

final class CreateOrganizationImpl extends AbstractUseCase implements CreateOrganization
{
    private readonly FileStore $files;
    private readonly GeoLocalizer $geoLocalizer;
    private readonly ProspectedOrganizations $prospectedOrganizations;

    public function __construct(
        private readonly OrganizationGateway $organizations,
        Storage $storage,
        private readonly UserNotifier $userNotifier,
        GeoLocation $geoLocation,
    ) {
        $this->files = new FileStore($storage);
        $this->geoLocalizer = new GeoLocalizer($geoLocation);
        $this->prospectedOrganizations = new ProspectedOrganizations($organizations);
    }

    public function canBeExecutedBy(): array
    {
        return ['ROLE_PROSPECTOR'];
    }

    public function execute(CreateOrganizationRequest $request, CreateOrganizationPresenter $presenter): mixed
    {
        $owner = $this->owner($request);
        $organization = $this->buildOrganization($request);
        $organization->setOwnedBy($owner);
        $organization->setHoldBy($this->prospectedOrganizations->holdingProspectedBy((int) $owner->getId(), $request->holdBy));
        $this->geoLocalizer->locate($organization);
        $this->validate($organization);

        $created = $this->create($organization);
        $this->organizations->update((int) $owner->getId(), $owner);
        $this->userNotifier->addSuccess('Organization created !');

        return $presenter->present($this->buildResponse($created));
    }

    private function buildOrganization(CreateOrganizationRequest $request): Organization
    {
        $organization = new Organization();
        $organization->setLanguage($request->language);
        $organization->setPhoneNumber($request->phoneNumber);
        $organization->setObservations($request->observations);
        $organization->setEmail($request->email);
        $organization->setCorporateName($request->corporateName);
        $organization->setForm($request->form);
        $organization->setType($request->type);
        $organization->setAddress($request->address);

        if ($request->logo) {
            $organization->setLogo($this->files->store($request->logo));
        }

        return $organization;
    }

    private function owner(CreateOrganizationRequest $request): Organization
    {
        if (null === $request->ownedBy) {
            throw new UseCaseException('Owner is missing', ['ownedBy' => 'Owner is missing']);
        }

        return $this->organizations->get($request->ownedBy);
    }

    private function validate(Organization $organization): void
    {
        try {
            $organization->validate();
        } catch (ValidationException $e) {
            throw new UseCaseException($e->getMessage(), [$e->getField() => $e->getMessage()], $e);
        }
    }

    private function create(Organization $organization): Organization
    {
        try {
            return $this->organizations->create($organization);
        } catch (Gateway\UniqueConstraintViolationException $e) {
            throw new UniqueConstraintViolationException('Email already used', ['email' => sprintf('Email "%s" already used', $organization->getEmail())], $e);
        }
    }

    private function buildResponse(Organization $organization): CreateOrganizationResponse
    {
        return new CreateOrganizationResponse(
            id: (int) $organization->getId(),
            ownedBy: (int) $organization->getOwnedBy()?->getId(),
            phoneNumber: $organization->getPhoneNumber(),
            email: $organization->getEmail(),
            language: $organization->getLanguage(),
            corporateName: $organization->getCorporateName(),
            form: $organization->getForm(),
            type: $organization->getType(),
            address: $organization->getAddress(),
            geoPoint: $organization->getGeoPoint(),
            observations: $organization->getObservations(),
            logo: $organization->getLogo(),
            holdBy: $organization->getHoldBy()?->getId(),
        );
    }
}
