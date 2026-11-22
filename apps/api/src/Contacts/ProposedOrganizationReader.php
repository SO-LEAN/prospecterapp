<?php

declare(strict_types=1);

namespace App\Contacts;

use App\Contacts\Resource\OrganizationProposedResource;
use App\Contacts\Resource\ProposedOrganizationResource;
use Prospecter\Core\PersonalData\Application\ConsultPerson\ConsultPerson;
use Prospecter\Core\PersonalData\Application\ConsultPerson\KeptPerson;
use Prospecter\Core\Prospecting\Application\ProposeOrganizationToAttach\KeptProposedOrganization;
use Prospecter\Core\Prospecting\Application\ProposeOrganizationToAttach\ProposeOrganizationToAttach;
use Prospecter\Core\SharedKernel\Application\QueryHandler;

/**
 * The organization proposed to attach a contact to, composed of two contexts (ADR 0146): PersonalData reads their
 * email in clear to the member with the right to manage personal data, Prospecting proposes the organization of its
 * domain, keeping no email; a contact without email has none proposed.
 */
final readonly class ProposedOrganizationReader
{
    public function __construct(
        private QueryHandler $consultPerson,
        private QueryHandler $proposeOrganizationToAttach,
    ) {
    }

    public function read(string $reader, string $contact): ProposedOrganizationResource
    {
        $person = new KeptPerson();
        $this->consultPerson->__invoke(new ConsultPerson($reader, $contact), $person);
        $email = $person->person->email;

        return new ProposedOrganizationResource(null === $email ? null : $this->proposedFor($reader, $email));
    }

    private function proposedFor(string $reader, string $email): ?OrganizationProposedResource
    {
        $proposed = new KeptProposedOrganization();
        $this->proposeOrganizationToAttach->__invoke(new ProposeOrganizationToAttach($reader, $email), $proposed);

        return null === $proposed->organization ? null : new OrganizationProposedResource($proposed->organization->id, $proposed->organization->name);
    }
}
