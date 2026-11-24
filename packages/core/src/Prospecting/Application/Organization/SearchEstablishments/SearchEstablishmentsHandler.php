<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\Organization\SearchEstablishments;

use Prospecter\Core\Prospecting\Application\OrganizationSheet;
use Prospecter\Core\Prospecting\Application\Port\OrganizationSheetRetriever;
use Prospecter\Core\Prospecting\Domain\Model\Establishment;
use Prospecter\Core\Prospecting\Domain\Model\OrganizationId;
use Prospecter\Core\Prospecting\Domain\Port\EstablishmentDirectory;
use Prospecter\Core\SharedKernel\Application\Handler\QueryHandler;

/**
 * Finds the establishments named like the organization: those of its municipality first, then those anywhere, each
 * once.
 */
final readonly class SearchEstablishmentsHandler implements QueryHandler
{
    public function __construct(
        private OrganizationSheetRetriever $organizationSheetRetriever,
        private EstablishmentDirectory $directory,
    ) {
    }

    public function __invoke(SearchEstablishments $query, SearchEstablishmentsOutput $output): void
    {
        $organization = $this->organizationSheetRetriever->get(new OrganizationId($query->organizationId));

        $output->present(array_values(array_map(self::found(...), self::eachOnce($this->namedLike($organization)))));
    }

    /**
     * @return list<Establishment>
     */
    private function namedLike(OrganizationSheet $organization): array
    {
        $name = $organization->corporateName;

        return null === $name ? [] : [...$this->inItsMunicipality($name, $organization->municipalityCode), ...$this->directory->named($name)];
    }

    /**
     * @return list<Establishment>
     */
    private function inItsMunicipality(string $name, ?string $municipalityCode): array
    {
        return null === $municipalityCode ? [] : $this->directory->namedIn($name, $municipalityCode);
    }

    /**
     * @param list<Establishment> $establishments
     *
     * @return array<string, Establishment>
     */
    private static function eachOnce(array $establishments): array
    {
        $once = [];

        foreach ($establishments as $establishment) {
            $once[$establishment->siret->value] ??= $establishment;
        }

        return $once;
    }

    private static function found(Establishment $establishment): FoundEstablishment
    {
        return new FoundEstablishment($establishment->siret->value, $establishment->name, $establishment->address, $establishment->activity, $establishment->closed);
    }
}
