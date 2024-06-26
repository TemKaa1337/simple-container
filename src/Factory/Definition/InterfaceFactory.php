<?php

declare(strict_types=1);

namespace Temkaa\SimpleContainer\Factory\Definition;

use Temkaa\SimpleContainer\Model\DefinitionInterface;
use Temkaa\SimpleContainer\Model\InterfaceDefinition;

/**
 * @internal
 */
final class InterfaceFactory
{
    /**
     * @param class-string $id
     * @param class-string $implementedById
     */
    public function create(string $id, string $implementedById): DefinitionInterface
    {
        return (new InterfaceDefinition())
            ->setId($id)
            ->setImplementedById($implementedById);
    }
}
