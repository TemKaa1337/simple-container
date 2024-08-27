<?php

declare(strict_types=1);

namespace Temkaa\SimpleContainer\Repository;

use Temkaa\SimpleContainer\Attribute\Autowire;
use Temkaa\SimpleContainer\Exception\EntryNotFoundException;
use Temkaa\SimpleContainer\Model\Definition\Bag;
use Temkaa\SimpleContainer\Model\Definition\ClassDefinition;
use Temkaa\SimpleContainer\Model\Definition\DefinitionInterface;
use Temkaa\SimpleContainer\Model\Definition\InterfaceDefinition;

/**
 * @internal
 */
#[Autowire(load: false)]
final readonly class DefinitionRepository
{
    public function __construct(
        private Bag $definitions,
    ) {
    }

    public function find(string $id): DefinitionInterface
    {
        /** @psalm-suppress ArgumentTypeCoercion */
        if ($this->definitions->has($id)) {
            /** @psalm-suppress ArgumentTypeCoercion */
            return $this->resolveDecorators($this->definitions->get($id));
        }

        if ($entry = $this->findOneByAlias($id)) {
            return $this->resolveDecorators($entry);
        }

        throw new EntryNotFoundException(sprintf('Could not find entry "%s".', $id));
    }

    /**
     * @return DefinitionInterface[]
     */
    public function findAllByTag(string $tag): array
    {
        $taggedDefinitions = [];
        foreach ($this->definitions as $definition) {
            if ($definition instanceof InterfaceDefinition) {
                continue;
            }

            /** @var ClassDefinition $definition */
            if (in_array($tag, $definition->getTags(), strict: true)) {
                $taggedDefinitions[] = $definition;
            }
        }

        return $taggedDefinitions;
    }

    public function has(string $id): bool
    {
        /** @psalm-suppress ArgumentTypeCoercion */
        return $this->definitions->has($id) || $this->findOneByAlias($id);
    }

    private function findOneByAlias(string $alias): ?DefinitionInterface
    {
        foreach ($this->definitions as $definition) {
            if ($definition instanceof InterfaceDefinition) {
                continue;
            }

            /** @var ClassDefinition $definition */
            if (in_array($alias, $definition->getAliases(), strict: true)) {
                return $definition;
            }
        }

        return null;
    }

    private function getRootDecoratorDefinition(DefinitionInterface $definition): DefinitionInterface
    {
        while ($definition->getDecoratedBy()) {
            /** @psalm-suppress PossiblyNullArgument */
            $definition = $this->definitions->get($definition->getDecoratedBy());
        }

        return $definition;
    }

    private function resolveDecorators(DefinitionInterface $definition): DefinitionInterface
    {
        if ($definition instanceof InterfaceDefinition) {
            if (!$definition->getDecoratedBy()) {
                return $this->definitions->get($definition->getImplementedById());
            }

            return $this->getRootDecoratorDefinition($definition);
        }

        /** @var ClassDefinition $definition */
        $decoratedBy = $definition->getDecoratedBy();
        $decorates = $definition->getDecorates();

        $isDecorationRoot = $decoratedBy && !$decorates;
        if (!$isDecorationRoot) {
            return $definition;
        }

        return $this->getRootDecoratorDefinition($definition);
    }
}
