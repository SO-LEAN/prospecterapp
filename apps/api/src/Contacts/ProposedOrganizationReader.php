<?php

declare(strict_types=1);

namespace App\Contacts;

use App\Contacts\Resource\OrganizationProposedResource;
use App\Contacts\Resource\ProposedOrganizationResource;
use Prospecter\Core\PersonalData\Application\Person\ConsultPerson\ConsultPerson;
use Prospecter\Core\PersonalData\Application\Person\ConsultPerson\KeptPerson;
use Prospecter\Core\Prospecting\Application\ProposeOrganizationToAttach\KeptProposedOrganization;
use Prospecter\Core\Prospecting\Application\ProposeOrganizationToAttach\ProposeOrganizationToAttach;
use Prospecter\Core\SharedKernel\Application\Handler\QueryHandler;

/**
 * Reads the organization proposed to attach a contact to, from two contexts (ADR 0146). PersonalData reads the
 * contact's email unmasked for a member with the right to manage personal data. Prospecting proposes the organization
 * of the email's domain and keeps no email. A contact without an email gets no proposal.
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
