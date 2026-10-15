<?php

declare(strict_types=1);

namespace Tests\Core\Double;

use Prospecter\Core\Entity\Base;

/**
 * Field equality as a repository understands it: a related entity is compared by its id.
 */
final class Criteria
{
    /**
     * @param array<string, mixed> $criteria
     */
    public static function match(object $entity, array $criteria): bool
    {
        foreach ($criteria as $field => $expected) {
            $value = $entity->{'get'.ucfirst($field)}();

            if (($value instanceof Base ? $value->getId() : $value) !== ($expected instanceof Base ? $expected->getId() : $expected)) {
                return false;
            }
        }

        return true;
    }
}
