<?php

declare( strict_types = 1 );

namespace Prospecter\Core\UseCase\UpdateOrganization;

use Prospecter\Core\Entity\Address;
use Prospecter\Core\Gateway\Storage;
use Prospecter\Core\Exception\Gateway;
use Prospecter\Core\Entity\Organization;
use Prospecter\Core\Gateway\GeoLocation;
use Prospecter\Core\Gateway\UserNotifier;
use Prospecter\Core\Traits\UseCase\StoreFileTrait;
use Prospecter\Core\Traits\UseCase\UserNotifierTrait;
use Prospecter\Core\UseCase\AbstractUseCase;
use Prospecter\Core\Traits\UseCase\GeoLocalizeTrait;
use Prospecter\Core\Exception\UseCase\UseCaseException;
use Prospecter\Core\Gateway\Entity\OrganizationGateway;
use Prospecter\Core\Exception\UseCase\NotFoundException;
use Prospecter\Core\Exception\Entity\ValidationException;
use Prospecter\Core\Exception\UseCase\UniqueConstraintViolationException;

final class UpdateOrganizationImpl extends AbstractUseCase implements UpdateOrganization
{
    use GeoLocalizeTrait;
    use StoreFileTrait;
    use UserNotifierTrait;
    /**
     * @var OrganizationGateway
     */
    private $organizationGateway;
    /**
     * @var Storage
     */
    private $storage;

    public function __construct(OrganizationGateway $organizationGateway, Storage $storage, UserNotifier $userNotifier, GeoLocation $geoLocation)
    {
        $this->organizationGateway = $organizationGateway;
        $this->storage = $storage;
        $this->userNotifier = $userNotifier;
        $this->geoLocation = $geoLocation;
    }

    public function canBeExecutedBy(): array
    {
        return ['ROLE_PROSPECTOR'];
    }

    public function execute(UpdateOrganizationRequest $request, UpdateOrganizationPresenter $presenter): ?object
    {

        $organization = $this->alterOrganization($request, $this->organizationGateway->get($request->getId()));
        $this->joinToHoldingIfNeeded($request, $organization);
        $this->geolocalize($organization);

        $this->validate($organization);
        $persisted = $this->update($organization);
        $response = $this->buildResponse($persisted);

        $this->notifySuccess('Organization updated !');

        return $presenter->present($response);
    }

    private function validate(Organization $organization): void
    {
        try {
            $organization->validate();
        } catch (ValidationException $e) {
            throw new UseCaseException($e->getMessage(), 412, $e, [$e->getField() => $e->getMessage()]);
        }
    }

    private function alterOrganization(UpdateOrganizationRequest $request, Organization $organization): Organization
    {

        $organization->setLanguage($request->getLanguage());
        $organization->setPhoneNumber($request->getPhoneNumber());
        $organization->setObservations($request->getObservations());
        $organization->setEmail($request->getEmail());
        $organization->setCorporateName($request->getCorporateName());
        $organization->setForm($request->getForm());
        $organization->setType($request->getType());

        if ($request->hasAddress()) {
            $organization->setAddress(Address::fromValues($request->getStreet(), $request->getPostalCode(), $request->getCity(), $request->getCountry()));
        } else {
            $organization->setAddress(null);
        }

        if ($request->getLogo()) {
            $organization->setLogo($this->storeFile($request->getLogo()));
        }

        return $organization;
    }

    private function joinToHoldingIfNeeded(UpdateOrganizationRequest $request, Organization $organization): void
    {
        if ($request->getHoldBy()) {
            try {
                $organization->setHoldBy($this->organizationGateway->get($request->getHoldBy()));
            } catch (Gateway\NotFoundException $e) {
                throw new NotFoundException(sprintf('Holding with #ID %d not found', $request->getHoldBy()), 404, $e, ['holdBy' => 'Holding not found']);
            }
        } else {
            $organization->setHoldBy(null);
        }
    }

    private function update(Organization $organization): Organization
    {
        try {
            $persisted = $this->organizationGateway->update($organization->getId(), $organization);
        } catch (Gateway\UniqueConstraintViolationException $e) {
            throw new UniqueConstraintViolationException('Email already used', 412, $e, ['email' => sprintf('Email "%s" already used', $organization->getEmail())]);
        }

        return $persisted;
    }

    private function buildResponse(Organization $persisted): UpdateOrganizationResponse
    {
        $response = new UpdateOrganizationResponse(
            $persisted->getId(),
            $persisted->getPhoneNumber(),
            $persisted->getEmail(),
            $persisted->getLanguage(),
            $persisted->getCorporateName(),
            $persisted->getForm(),
            $persisted->getType(),
            $persisted->getAddress() ? $persisted->getAddress()->getStreet() : null,
            $persisted->getAddress() ? $persisted->getAddress()->getPostalCode() : null,
            $persisted->getAddress() ? $persisted->getAddress()->getCity() : null,
            $persisted->getAddress() ? $persisted->getAddress()->getCountry() : null,
            $persisted->getGeoPoint() ? $persisted->getGeoPoint()->getLongitude() : null,
            $persisted->getGeoPoint() ? $persisted->getGeoPoint()->getLatitude() : null,
            $persisted->getObservations(),
            $persisted->getLogo() ? $persisted->getLogo()->getUrl() : null,
            $persisted->getLogo() ? $persisted->getLogo()->getExtension() : null,
            $persisted->getLogo() ? $persisted->getLogo()->getSize() : null,
            $persisted->getHoldBy() ? $persisted->getHoldBy()->getId() : null
        );

        return $response;
    }
}
