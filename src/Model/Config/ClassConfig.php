<?php

declare(strict_types=1);

namespace Temkaa\Container\Model\Config;

use Temkaa\Container\Attribute\Bind\InstanceOfIterator;
use Temkaa\Container\Attribute\Bind\TaggedIterator;
use UnitEnum;

/**
 * @internal
 */
final readonly class ClassConfig
{
    /**
     * @param class-string                                                     $class
     * @param string[]                                                         $aliases
     * @param array<string, string|InstanceOfIterator|TaggedIterator|UnitEnum> $boundVariables
     * @param Decorator|null                                                   $decorates
     * @param bool                                                             $singleton
     * @param string[]                                                         $tags
     * @param Factory|null                                                     $factory
     * @param string[]                                                         $methodCalls
     */
    public function __construct(
        private string $class,
        private array $aliases,
        private array $boundVariables,
        private ?Decorator $decorates,
        private bool $singleton,
        private array $tags,
        private ?Factory $factory,
        private array $methodCalls,
    ) {
    }

    public function getAliases(): array
    {
        return $this->aliases;
    }

    /**
     * @return array<string, string|InstanceOfIterator|TaggedIterator|UnitEnum>
     */
    public function getBoundedVariables(): array
    {
        return $this->boundVariables;
    }

    /**
     * @return class-string
     */
    public function getClass(): string
    {
        return $this->class;
    }

    public function getDecorates(): ?Decorator
    {
        return $this->decorates;
    }

    public function getFactory(): ?Factory
    {
        return $this->factory;
    }

    /**
     * @return string[]
     */
    public function getMethodCalls(): array
    {
        return $this->methodCalls;
    }

    /**
     * @return string[]
     */
    public function getTags(): array
    {
        return $this->tags;
    }

    public function isSingleton(): bool
    {
        return $this->singleton;
    }
}
