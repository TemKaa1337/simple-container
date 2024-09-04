<?php

declare(strict_types=1);

namespace Temkaa\SimpleContainer\Util;

use Temkaa\SimpleContainer\Model\Config;
use Temkaa\SimpleContainer\Model\Config\Factory;
use UnitEnum;

/**
 * @internal
 */
final class BoundVariableProvider
{
    /**
     * @param Config       $config
     * @param string       $argumentName
     * @param class-string $id
     * @param Factory|null $factory
     *
     * @return string|UnitEnum|null
     */
    public static function provide(
        Config $config,
        string $argumentName,
        string $id,
        ?Factory $factory,
    ): null|string|UnitEnum {
        $classBinding = $config->getBoundedClass($id);
        $classBoundVars = $classBinding?->getBoundedVariables() ?? [];
        $classFactoryBindings = $factory?->getBoundedVariables() ?? [];

        $globalBoundVars = $config->getBoundedVariables();

        return $factory
            ? $classFactoryBindings[$argumentName] ?? $globalBoundVars[$argumentName] ?? null
            : $classBoundVars[$argumentName] ?? $globalBoundVars[$argumentName] ?? null;
    }
}
