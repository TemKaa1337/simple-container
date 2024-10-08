<?php

declare(strict_types=1);

namespace Temkaa\Container\Model\Config;

use Temkaa\Container\Attribute\Bind\InstanceOfIterator;
use Temkaa\Container\Attribute\Bind\TaggedIterator;
use UnitEnum;

/**
 * @internal
 */
final readonly class Factory
{
    /**
     * @param class-string                                                     $id
     * @param string                                                           $method
     * @param array<string, string|InstanceOfIterator|TaggedIterator|UnitEnum> $boundedVariables
     */
    public function __construct(
        private string $id,
        private string $method,
        private array $boundedVariables,
    ) {
    }

    /**
     * @return array<string, string|InstanceOfIterator|TaggedIterator|UnitEnum>
     */
    public function getBoundedVariables(): array
    {
        return $this->boundedVariables;
    }

    /**
     * @return class-string
     */
    public function getId(): string
    {
        return $this->id;
    }

    public function getMethod(): string
    {
        return $this->method;
    }
}
