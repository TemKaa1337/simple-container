<?php

declare(strict_types=1);

namespace Temkaa\SimpleContainer\Validator;

use Temkaa\SimpleContainer\Exception\DuplicatedEntryAliasException;
use Temkaa\SimpleContainer\Model\ClassDefinition;
use Temkaa\SimpleContainer\Model\DefinitionInterface;

/**
 * @internal
 */
final readonly class DuplicatedDefinitionAliasValidator
{
    /**
     * @param DefinitionInterface[] $definitions
     */
    public function validate(array $definitions): void
    {
        $uniqueAliases = [];

        foreach ($definitions as $definition) {
            if (!$definition instanceof ClassDefinition) {
                continue;
            }

            foreach ($definition->getAliases() as $alias) {
                if (isset($uniqueAliases[$alias])) {
                    throw new DuplicatedEntryAliasException($alias, $uniqueAliases[$alias], $definition->getId());
                }

                $uniqueAliases[$alias] = $definition->getId();
            }
        }
    }
}
