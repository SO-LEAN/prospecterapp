<?php

declare(strict_types=1);

namespace Prospecter\Core\UseCase\UpdateOrganization;

use Prospecter\Core\Entity\Organization;
use Prospecter\Core\Exception\Entity\ValidationException;
use Prospecter\Core\Exception\Gateway;
use Prospecter\Core\Exception\UseCase\NotFoundException;
use Prospecter\Core\Exception\UseCase\UniqueConstraintViolationException;
use Prospecter\Core\Exception\UseCase\UseCaseException;
use Prospecter\Core\Gateway\Entity\OrganizationGateway;
use Prospecter\Core\Gateway\GeoLocation;
use Prospecter\Core\Gateway\Storage;
use Prospecter\Core\Gateway\UserNotifier;
use Prospecter\Core\Service\FileStore;
use Prospecter\Core\Service\GeoLocalizer;
use Prospecter\Core\UseCase\AbstractUseCase;

final class UpdateOrganizationImpl extends AbstractUseCase implements UpdateOrganization
{
    private readonly FileStore $files;
    private readonly GeoLocalizer $geoLocalizer;

    public function __construct(
        private readonly OrganizationGateway $organizations,
        Storage $storage,
        private readonly UserNotifier $userNotifier,
        GeoLocation $geoLocation,
    ) {
        $this->files = new FileStore($storage);
        $this->geoLocalizer = new GeoLocalizer($geoLocation);
    }

    public function canBeExecutedBy(): array
    {
        return ['ROLE_PROSPECTOR'];
    }

    public function execute(UpdateOrganizationRequest $request, UpdateOrganizationPresenter $presenter): mixed
    {
        $organization = $this->organizations->get($request->id);
        $this->replaceDetails($organization, $request);
        $organization->setHoldBy($this->holding($request));
        $this->geoLocalizer->locate($organization);
        $this->validate($organization);

        $updated = $this->update($organization);
        $this->userNotifier->addSuccess('Organization updated !');

        return $presenter->present($this->buildResponse($updated));
    }

    private function replaceDetails(Organization $organization, UpdateOrganizationRequest $request): void
    {
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
    }

    private function holding(UpdateOrganizationRequest $request): ?Organization
    {
        if (null === $request->holdBy) {
            return null;
        }

        try {
            return $this->organizations->get($request->holdBy);
        } catch (Gateway\NotFoundException $e) {
            throw new NotFoundException(sprintf('Holding with #ID %d not found', $request->holdBy), ['holdBy' => 'Holding not found'], $e);
        }
    }

    private function validate(Organization $organization): void
    {
        try {
            $organization->validate();
        } catch (ValidationException $e) {
            throw new UseCaseException($e->getMessage(), [$e->getField() => $e->getMessage()], $e);
        }
    }

    private function update(Organization $organization): Organization
    {
        try {
            return $this->organizations->update((int) $organization->getId(), $organization);
        } catch (Gateway\UniqueConstraintViolationException $e) {
            throw new UniqueConstraintViolationException('Email already used', ['email' => sprintf('Email "%s" already used', $organization->getEmail())], $e);
        }
    }

    private function buildResponse(Organization $organization): UpdateOrganizationResponse
    {
        return new UpdateOrganizationResponse(
            id: (int) $organization->getId(),
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
